<?php

namespace Tests\Feature;

use App\Models\Domain;
use Tests\TestCase;

/**
 * Round-35 附带修复回归：Domain::nameservers_list accessor
 * 背景：NS 双格式兼容逻辑原先内联在 domains/show.blade.php 的 @php(...) 表达式里，
 * 嵌套括号经 Laravel 13.29 编译产物形如 `<?php(...)`（缺 `; ?>`），
 * PHP 8.3.30 无法解析（线上 500），8.3.33+ 容忍（本地测试全绿未暴露）。
 * 修复：逻辑收口到模型 accessor，blade 只做展示。
 */
class DomainNameserversListTest extends TestCase
{
    public function test_parses_new_json_array_format(): void
    {
        $domain = new Domain(['monitor_nameservers' => '["meteorologist.dnspod.net","zenobia.dnspod.net"]']);

        $this->assertSame(['meteorologist.dnspod.net', 'zenobia.dnspod.net'], $domain->nameservers_list);
    }

    public function test_parses_legacy_comma_separated_format(): void
    {
        $domain = new Domain(['monitor_nameservers' => 'ns1.example.com, ns2.example.com']);

        $this->assertSame(['ns1.example.com', 'ns2.example.com'], $domain->nameservers_list);
    }

    public function test_returns_empty_list_for_null_and_blank(): void
    {
        $this->assertSame([], (new Domain(['monitor_nameservers' => null]))->nameservers_list);
        $this->assertSame([], (new Domain(['monitor_nameservers' => '']))->nameservers_list);
        $this->assertSame([], (new Domain(['monitor_nameservers' => '   ']))->nameservers_list);
    }

    public function test_filters_blank_entries_and_coerces_scalars(): void
    {
        $domain = new Domain(['monitor_nameservers' => '["ns1.example.com", "", "  ", 42, null]']);

        $this->assertSame(['ns1.example.com', '42'], $domain->nameservers_list);
    }
}
