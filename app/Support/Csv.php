<?php

namespace App\Support;

/**
 * CSV 导出工具（安全审计周期 #8：公式注入防护）
 *
 * 防公式注入：访客上报/用户提交的字符串字段（referrer、UA、URL、
 * 备注等）进入 CSV 后，Excel/LibreOffice 会把以 = + - @ TAB CR 开头的
 * 单元格当公式执行（DDE 下载执行、HYPERLINK 钓鱼等）。统一在单元格
 * 首字符前加撇号使其按文本处理。
 */
class Csv
{
    /**
     * 返回类型可安全传给 fputcsv（scalar 或 null）
     */
    public static function sanitizeCell(mixed $value): bool|float|int|string|null
    {
        if (! is_string($value) || $value === '') {
            return is_scalar($value) || $value === null ? $value : '';
        }

        if (str_starts_with($value, '=') || str_starts_with($value, '+')
            || str_starts_with($value, '-') || str_starts_with($value, '@')
            || str_starts_with($value, "\t") || str_starts_with($value, "\r")) {
            return "'".$value;
        }

        return $value;
    }
}
