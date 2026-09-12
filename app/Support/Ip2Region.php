<?php

namespace App\Support;

use ip2region\xdb\IPv4;
use ip2region\xdb\Searcher;

/**
 * ip2region 中文省/市离线查询（中国 IP 补充数据源）
 *
 * 背景：db-ip city lite（mmdb）的 city/subdivisions 仅有英文名（names 键无
 * zh-CN），中国访客在统计页只能看到 Beijing/Hangzhou 等英文/拼音。ip2region
 * v4 xdb 为离线中文库（约 11MB，全球 IPv4 段、中国段精确到省/市/ISP），返回
 * 「国家|省份|城市|ISP|国家码」格式（如 中国|浙江省|杭州市|电信|CN）。
 *
 * 数据源优先级（GeoIp::lookup 内）：中国 IP 且 ip2region 能给出省/市时优先
 * 采用（中文增益），其余情况回退 mmdb（保留海外英文城市与经纬度能力）。
 *
 * 实现要点：
 * - 使用 app/Support/ip2region/Searcher.class.php 官方 binding（原样引入，
 *   保留 ip2region\xdb 命名空间，运行时 require_once）
 * - file-only 模式：查询时按向量索引 2~3 次 fread 定位，无内存常驻，FPM
 *   长驻进程以静态实例复用文件句柄
 * - xdb 由 geoip:update 下载到 storage/app/geoip/ip2region.xdb；未部署 /
 *   查询异常 / 非中国 IP 一律静默返回 null，不影响采集链路
 */
class Ip2Region
{
    /** 进程内复用的 searcher 实例（file-only 模式，随路径变化重建） */
    protected static ?object $searcher = null;

    protected static ?string $searcherPath = null;

    /**
     * 查询 IP 的中文省/市（仅当能带来中文地理增益——中国 IP 且省/市至少一项可用——时返回）
     *
     * 返回 null 的情形：非中国 IP、库未部署、查询失败、省市全缺失
     * （如「中国|0|0|0|CN」专网段），调用方回退 mmdb。
     *
     * @return array{country_code: string, region_name: ?string, city_name: ?string}|null
     */
    public static function lookup(?string $ip): ?array
    {
        if (($ip === null || $ip === '') || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null;
        }

        $path = Typed::string(config('services.geoip.ip2region_path'));

        if ($path === '' || ! is_file($path)) {
            return null;
        }

        $searcher = static::searcher($path);

        if ($searcher === null) {
            return null;
        }

        $region = static::searchSafe($searcher, $ip);

        if ($region === null) {
            return null;
        }

        return static::parseRegion($region);
    }

    /**
     * 重置进程内 searcher 实例（xdb 文件更新后调用，测试亦用）
     */
    public static function flush(): void
    {
        static::$searcher = null;
        static::$searcherPath = null;
    }

    /**
     * 解析「国家|省份|城市|ISP|国家码」记录（ip2region 缺失字段以「0」占位）
     *
     * @return array{country_code: string, region_name: ?string, city_name: ?string}|null
     */
    protected static function parseRegion(string $region): ?array
    {
        $parts = explode('|', $region);
        $country = trim($parts[0]);
        $province = static::cleanField($parts[1] ?? '0');
        $city = static::cleanField($parts[2] ?? '0');
        $countryCode = strtoupper(Typed::string(static::cleanField($parts[4] ?? '0')));

        // 仅中国 IP 采用（海外段虽有记录但省市为英文/占位，回退 mmdb 保留英文城市与经纬度）
        if ($country !== '中国' && $countryCode !== 'CN') {
            return null;
        }

        if ($province === null && $city === null) {
            return null;
        }

        // 直辖市省/市同名（如 北京市|北京市）→ 市置空：展示层 CONCAT_WS(' · ', region, city)
        // 跳过空值，避免「北京市 · 北京市」重复
        if ($city !== null && $city === $province) {
            $city = null;
        }

        return [
            'country_code' => ($countryCode !== '') ? $countryCode : 'CN',
            'region_name' => $province,
            'city_name' => $city,
        ];
    }

    /**
     * 清洗记录字段：空串 / 「0」占位 → null
     */
    protected static function cleanField(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || $value === '0') {
            return null;
        }

        return $value;
    }

    /**
     * 经由上游 binding 查询单个 IP
     *
     * Searcher 为上游原样引入的非 PSR-4 类（运行时 require_once 后才可见，
     * 静态分析对其豁免），故此处以 object 动态调用并显式豁免 method.notFound。
     */
    protected static function searchSafe(object $searcher, string $ip): ?string
    {
        try {
            $region = $searcher->search($ip); // @phpstan-ignore method.notFound (上游 binding 运行时加载，见 ip2region/Searcher.class.php 头注释)
        } catch (\Throwable) {
            return null;
        }

        if (! is_string($region) || $region === '') {
            return null;
        }

        return $region;
    }

    /**
     * 取（并缓存）指定路径 xdb 的 searcher 实例
     */
    protected static function searcher(string $path): ?object
    {
        if (static::$searcher !== null && static::$searcherPath === $path) {
            return static::$searcher;
        }

        $binding = __DIR__.'/ip2region/Searcher.class.php';

        if (! is_file($binding)) {
            return null;
        }

        require_once $binding;

        try {
            /** @var object $searcher */
            // @phpstan-ignore-next-line 上游 binding 运行时 require 后才可见（见 ip2region/Searcher.class.php 头注释）
            $searcher = Searcher::newWithFileOnly(IPv4::default(), $path);
        } catch (\Throwable) {
            return null;
        }

        static::$searcher = $searcher;
        static::$searcherPath = $path;

        return $searcher;
    }
}
