<?php

namespace App\Support;

use App\Models\Plan;

/**
 * 多货币支持（规格书 §10.4：多货币与汇率）
 *
 * - 默认支付货币：settings payment.currency（默认 CNY），基准汇率恒为 1
 * - 货币清单：config 预设 + settings payment.currencies（JSON，可覆盖/新增任意货币）
 * - rate 含义：1 默认货币 = rate 目标货币（如 1 CNY = 0.14 USD）
 * - 计划价：优先 plans.prices 嵌套形态 {CODE:{monthly,annual|yearly,lifetime}} 的直配价，
 *   无直配价时以默认货币价格 × 汇率换算（修复此前无价货币下单金额为 0 的问题）
 */
class Currency
{
    /** 默认（基准）货币代码 */
    public static function default(): string
    {
        $code = strtoupper(trim((string) Settings::get('payment.currency', '')));

        if ($code === '') {
            $code = strtoupper((string) config('monit.payment.default_currency', 'CNY'));
        }

        return preg_match('/^[A-Z]{3}$/', $code) ? $code : 'CNY';
    }

    /**
     * 全部可用货币：[CODE => ['name' =>, 'symbol' =>, 'rate' => float]]
     * 默认货币强制 rate=1 且置顶；settings 配置可覆盖预设行或新增任意货币
     */
    public static function all(): array
    {
        $currencies = collect(config('monit.payment.currencies', []))
            ->map(fn ($row) => static::normalizeRow((array) $row))
            ->all();

        // settings payment.currencies 覆盖/扩展
        // （AdminSettings 保存经 json cast 读回为数组；Settings::set 存 JSON 字符串则读回字符串）
        $stored = Settings::get('payment.currencies');

        if (is_string($stored) && $stored !== '') {
            $stored = json_decode($stored, true);
        }

        if (is_array($stored)) {
            foreach ($stored as $code => $row) {
                $code = strtoupper(trim((string) $code));

                if (preg_match('/^[A-Z]{3}$/', $code)) {
                    $currencies[$code] = static::normalizeRow(array_merge(
                        $currencies[$code] ?? [],
                        (array) $row,
                    ));
                }
            }
        }

        // 默认货币恒为基准（rate=1）并置顶
        $default = static::default();

        $defaultRow = static::normalizeRow(array_merge(
            ['name' => '默认货币', 'symbol' => '', 'rate' => 1],
            $currencies[$default] ?? [],
            ['rate' => 1],
        ));

        unset($currencies[$default]);

        return [$default => $defaultRow] + $currencies;
    }

    protected static function normalizeRow(array $row): array
    {
        $rate = (float) ($row['rate'] ?? 1);

        return [
            'name' => (string) ($row['name'] ?? ''),
            'symbol' => (string) ($row['symbol'] ?? ''),
            'rate' => $rate > 0 ? $rate : 1,
        ];
    }

    public static function isEnabled(string $code): bool
    {
        return isset(static::all()[strtoupper(trim($code))]);
    }

    /** 非法货币代码回退默认货币 */
    public static function normalize(?string $code): string
    {
        $code = strtoupper(trim((string) $code));

        return static::isEnabled($code) ? $code : static::default();
    }

    public static function symbol(string $code): string
    {
        return (string) (static::all()[strtoupper(trim($code))]['symbol'] ?? '');
    }

    /** 汇率：1 默认货币 = rate 该货币 */
    public static function rate(string $code): float
    {
        return (float) (static::all()[strtoupper(trim($code))]['rate'] ?? 1);
    }

    /**
     * 金额换算：默认 from = 默认货币；round 2 位小数
     */
    public static function convert(float $amount, string $to, ?string $from = null): float
    {
        $from ??= static::default();

        $rate = static::rate($to) / static::rate($from);

        return round($amount * $rate, 2);
    }

    /**
     * 计划价（规格书 §10.4），回退顺序：
     * 1) 该货币直配价 prices[CODE][freq]（annual 兼容 yearly 键）
     * 2) 默认货币直配价 → 按汇率换算
     * 3) 扁平形态 prices[monthly/yearly/lifetime]（视为默认货币定价）→ 换算
     * 4) 任意其它货币直配价 → 跨汇率换算（兼容旧数据：切换默认货币后原价仍可展示）
     * 均无 → null（调用方不得以 0 元下单）
     *
     * @param  array|Plan|null  $plan  Plan 模型或 prices 数组
     */
    public static function planPrice($plan, string $currency, string $frequency): ?float
    {
        $prices = $plan instanceof Plan ? ($plan->prices ?? []) : (array) ($plan ?? []);
        $currency = strtoupper(trim($currency));
        $keys = $frequency === 'annual' ? ['annual', 'yearly'] : [$frequency];
        $default = static::default();

        $pick = function (array $entry) use ($keys): ?float {
            foreach ($keys as $key) {
                if (isset($entry[$key]) && is_numeric($entry[$key])) {
                    return round((float) $entry[$key], 2);
                }
            }

            return null;
        };

        // 1) 该货币直配价（嵌套形态）
        if (isset($prices[$currency]) && is_array($prices[$currency])) {
            if (($direct = $pick($prices[$currency])) !== null) {
                return $direct;
            }
        }

        // 2) 默认货币直配价 → 按汇率换算
        if (isset($prices[$default]) && is_array($prices[$default])) {
            if (($base = $pick($prices[$default])) !== null) {
                return static::convert($base, $currency);
            }
        }

        // 3) 扁平形态（无货币层级，视为默认货币定价）
        if (($flat = $pick($prices)) !== null) {
            return static::convert($flat, $currency);
        }

        // 4) 任意其它货币直配价 → 跨汇率换算
        foreach ($prices as $code => $entry) {
            if (is_array($entry) && ($any = $pick($entry)) !== null) {
                return static::convert($any, $currency, strtoupper((string) $code));
            }
        }

        return null;
    }
}
