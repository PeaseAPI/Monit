<?php

namespace App\Support;

/**
 * 外部输入（webhook payload / HTTP 请求 / JSON 解码 / 第三方响应）的类型化取值工具。
 *
 * 背景：PHPStan level 9 起，对隐式 mixed（$request->input()、json_decode、
 * config() 等返回值）的 (string)/(int) 强转与 offset 访问全部报错。本类把
 * 窄化逻辑集中到一处：方法签名接受 mixed，内部窄化后返回具体标量类型，
 * 调用点不再出现对 mixed 的裸 cast。
 *
 * 语义与 PHP cast 对齐（避免行为漂移）：
 * - 数字字符串可转对应数值（'12' -> 12、'12.5' -> 12.5）
 * - 非数值标量转数值类型返回默认值（'abc' -> 0，与 (int) 'abc' 一致）
 * - bool 保持 PHP truthy 语义（'false' 字符串为 true，与 (bool) 一致）
 * - null / 数组 / 对象转标量返回默认值（比裸 cast 更防御：静默降级而非告警）
 */
final class Typed
{
    public static function string(mixed $value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $default;
    }

    public static function int(mixed $value, int $default = 0): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    public static function float(mixed $value, float $default = 0.0): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return $default;
    }

    public static function bool(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        return (bool) $value;
    }

    /** 窄化为 ?string：非字符串标量原样转，null/数组/对象返回 null */
    public static function stringOrNull(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    /** 窄化为 ?int：数字字符串可转，其余（含 null）返回 null */
    public static function intOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public static function arr(mixed $value, array $default = []): array
    {
        if (is_array($value)) {
            /** @var array<string, mixed> $value */
            return $value;
        }

        return $default;
    }

    /**
     * 非空字符串窄化（等价旧 `?: 默认值` 语义）：null / false / 非字符串 / 空串均回退默认值。
     * 用于 trim()、mb_substr()、implode() 等恒非 null 但可能为空的函数结果。
     */
    public static function nonEmpty(mixed $value, string $default): string
    {
        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * 窄化为字符串列表（glob / preg_split 等返回 list<string>|false 的函数专用）。
     * false / null / 非 list 输入一律返回空列表；元素中的非字符串被过滤。
     *
     * @return list<string>
     */
    public static function strList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * 窄化 DNS 记录列表（dns_get_record 返回 list<array<string, mixed>>|false）。
     * false / null / 非数组一律返回空列表，保留每条记录的键值形状。
     *
     * @return list<array<string, mixed>>
     */
    public static function dnsRecords(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        /** @var list<array<string, mixed>> $value */
        return $value;
    }

    /** 点路径取值并窄化为 string（data_get 语义，取不到/类型不符返回默认值） */
    public static function stringPath(mixed $value, string $path, string $default = ''): string
    {
        return self::string(data_get($value, $path), $default);
    }

    /** 点路径取值并窄化为 int */
    public static function intPath(mixed $value, string $path, int $default = 0): int
    {
        return self::int(data_get($value, $path), $default);
    }

    /**
     * 点路径取值并窄化为 array
     *
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public static function arrPath(mixed $value, string $path, array $default = []): array
    {
        return self::arr(data_get($value, $path), $default);
    }
}
