<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $domain_id
 * @property int $user_id
 * @property string $scheme
 * @property string $host
 * @property int $type
 * @property bool $is_enabled
 * @property Carbon|null $datetime
 * @property bool $monitor_is_enabled
 * @property Carbon|string|null $monitor_expiration_date
 * @property string|null $monitor_registrar
 * @property string|null $monitor_nameservers
 * @property string|null $monitor_ssl
 * @property Carbon|null $monitor_last_check_at
 */
class Domain extends Model
{
    protected $primaryKey = 'domain_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'scheme', 'host', 'type', 'is_enabled', 'datetime',
        // SEO 模块：域名监控（whois 到期 / registrar / NS / SSL）
        'monitor_is_enabled', 'monitor_expiration_date', 'monitor_registrar',
        'monitor_nameservers', 'monitor_ssl', 'monitor_last_check_at',
    ];

    /**
     * @return array<string, string|\Stringable>
     */
    protected function casts(): array
    {
        return [
            'type' => 'integer', // 0=用户自定义域名 / 1=平台主域名（§3.1）
            'is_enabled' => 'boolean',
            'datetime' => 'datetime',
            // SEO 模块
            'monitor_is_enabled' => 'boolean',
            'monitor_expiration_date' => 'date',
            'monitor_last_check_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website()
    {
        return $this->belongsTo(Website::class, 'domain_id', 'domain_id');
    }

    /**
     * NS 列表（任务 #35-5 双格式兼容：新数据为 JSON 数组、旧数据为逗号分隔字符串）
     *
     * 注意：此逻辑勿用 blade `@php(...)` 内联表达式承载——嵌套括号表达式经
     * Laravel 13.29 编译后产物形如 `<?php(...)`（缺失 `; ?>`），PHP 8.3.30
     * 无法解析而 8.3.33+ 容忍，线上域名详情页曾因此 500。
     *
     * @return array<int, string>
     */
    public function getNameserversListAttribute(): array
    {
        if ($this->monitor_nameservers === null || trim($this->monitor_nameservers) === '') {
            return [];
        }

        $decoded = json_decode($this->monitor_nameservers, true);

        if (is_array($decoded)) {
            $nameservers = array_map(fn ($ns): string => trim((string) $ns), $decoded);

            return array_values(array_filter($nameservers, fn (string $ns): bool => $ns !== ''));
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $this->monitor_nameservers)),
            fn (string $ns): bool => $ns !== ''
        ));
    }
}
