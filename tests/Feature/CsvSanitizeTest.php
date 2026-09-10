<?php

namespace Tests\Feature;

use App\Support\Csv;
use PHPUnit\Framework\TestCase;

/**
 * 安全审计周期 #8：CSV 公式注入防护
 *
 * Excel/LibreOffice 会把以 = + - @ TAB CR 开头的单元格当公式执行
 * （DDE 下载执行、HYPERLINK 钓鱼）——访客上报字段进 CSV 必须转义
 */
class CsvSanitizeTest extends TestCase
{
    public function test_formula_prefixes_are_neutralized(): void
    {
        foreach (['=cmd|/c calc!', '+SUM(A1)', '-1', '@SUM(A1)', "\tTab", "\rCR"] as $payload) {
            $this->assertSame(
                "'".$payload,
                Csv::sanitizeCell($payload),
                '应转义公式前缀'
            );
        }
    }

    public function test_safe_values_are_untouched(): void
    {
        $this->assertSame('normal', Csv::sanitizeCell('normal'));
        $this->assertSame('a=b inside', Csv::sanitizeCell('a=b inside'));
        $this->assertSame('', Csv::sanitizeCell(''));
        $this->assertNull(Csv::sanitizeCell(null));
        $this->assertSame(123, Csv::sanitizeCell(123));
        $this->assertSame(0, Csv::sanitizeCell(0));
    }
}
