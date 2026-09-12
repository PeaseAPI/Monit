<?php

namespace Tests\Feature;

use App\Services\Seo\DomainMonitor;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * M30：域名监控 RDAP HTTP 回退通道
 * 背景：whois 43 端口为明文 TCP，云主机安全组常默认禁出站 →
 * 「注册商/到期时间获取不到」。socket 失败时回退 rdap.org（HTTP 443）。
 */
class DomainMonitorRdapTest extends TestCase
{
    public function test_rdap_parses_expiration_registrar_and_nameservers(): void
    {
        Http::fake([
            'rdap.org/domain/*' => Http::response([
                'events' => [
                    ['eventAction' => 'registration', 'eventDate' => '2020-01-01T00:00:00Z'],
                    ['eventAction' => 'expiration', 'eventDate' => '2027-08-31T04:00:00Z'],
                ],
                'entities' => [
                    ['roles' => ['registrar'], 'vcard' => ['vcard', [['fn', [], 'text', 'Aliyun (Hangzhou) Co., Ltd.']]]],
                ],
                'nameservers' => [
                    ['ldhName' => 'NS1.ALIYUN.COM'],
                    ['ldhName' => 'ns2.aliyun.com'],
                ],
            ]),
        ]);

        $result = (new DomainMonitor)->rdap('example.com');

        $this->assertNotNull($result);
        $this->assertSame('2027-08-31', $result['expiration_date'] ?? null);
        $this->assertSame('Aliyun (Hangzhou) Co., Ltd.', $result['registrar'] ?? null);
        $this->assertSame(['ns1.aliyun.com', 'ns2.aliyun.com'], $result['nameservers'] ?? null);
    }

    public function test_whois_falls_back_to_rdap_when_socket_channel_fails(): void
    {
        Http::fake([
            'rdap.org/domain/*' => Http::response([
                'events' => [['eventAction' => 'expiration', 'eventDate' => '2027-01-15T00:00:00Z']],
                'entities' => [['roles' => ['registrar'], 'handle' => '9999-XYZ']],
            ]),
        ]);

        // socket whois 通道失败（43 端口被禁的线上场景）
        $monitor = new class extends DomainMonitor
        {
            protected function socketWhois(string $domain): array
            {
                return ['ok' => false, 'error' => 'whois 服务器连接失败'];
            }
        };

        $result = $monitor->whois('example.com');

        $this->assertTrue($result['ok']);
        $this->assertSame('2027-01-15', $result['expiration_date'] ?? null);
        $this->assertSame('9999-XYZ', $result['registrar'] ?? null);
    }

    public function test_whois_prefers_socket_result_and_merges_missing_fields_from_rdap(): void
    {
        Http::fake([
            'rdap.org/domain/*' => Http::response([
                'events' => [['eventAction' => 'expiration', 'eventDate' => '2099-01-01T00:00:00Z']],
            ]),
        ]);

        // socket 成功拿到到期日但缺 registrar → RDAP 到期日不覆盖，RDAP registrar 缺失也不覆盖
        $monitor = new class extends DomainMonitor
        {
            protected function socketWhois(string $domain): array
            {
                return ['ok' => true, 'expiration_date' => '2028-12-31', 'registrar' => null, 'nameservers' => ['ns1.example.com']];
            }
        };

        $result = $monitor->whois('example.com');

        $this->assertTrue($result['ok']);
        $this->assertSame('2028-12-31', $result['expiration_date'] ?? null); // socket 值优先
        $this->assertSame(['ns1.example.com'], $result['nameservers'] ?? null);
    }

    public function test_rdap_returns_null_on_http_error(): void
    {
        Http::fake(['rdap.org/domain/*' => Http::response('not found', 404)]);

        $this->assertNull((new DomainMonitor)->rdap('nonexistent.invalid'));
    }
}
