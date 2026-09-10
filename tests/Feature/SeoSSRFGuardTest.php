<?php

namespace Tests\Feature;

use App\Services\Seo\AuditEngine;
use App\Services\Seo\Tools\DevTools;
use App\Services\Seo\Tools\NetworkTools;
use App\Services\Seo\Tools\SeoCheckTools;
use Tests\TestCase;

/**
 * 安全审计周期 #6：SEO 抓取面 SSRF 防护
 *
 * 缺陷（修复前）：SEO 工具族（SeoCheckTools/NetworkTools/DevTools）与
 * audit 引擎对用户提交的 URL 直接 Http::get()，无任何内网/环回/云元数据
 * 目标校验，且错误消息回显 → 非盲 SSRF（可探测 127.0.0.1、169.254.169.254
 * 等并读取内容）。防护复用 webhook 已有的 WebhookSignature::isSafeHttpUrl
 */
class SeoSSRFGuardTest extends TestCase
{
    public function test_guard_blocks_private_and_metadata_targets(): void
    {
        $blocked = [
            'http://127.0.0.1:8888/',              // 环回
            'http://[::1]/',                        // IPv6 环回
            'http://169.254.169.254/latest/meta-data', // 云元数据
            'http://10.0.0.1/',                     // 私网 A 类
            'http://192.168.1.1/',                  // 私网 C 类
            'http://172.16.0.1/',                   // 私网 B 类
            'ftp://example.com/',                   // 非 http(s) scheme
            '',                                     // 空
            'not a url at all',                     // 无 host
        ];

        foreach ($blocked as $url) {
            $this->assertNotNull(
                AuditEngine::rejectUnsafeUrl($url),
                "应拦截: {$url}"
            );
        }
    }

    public function test_guard_allows_public_literal_ip(): void
    {
        // 字面公网 IP 不经 DNS 判定，测试环境稳定
        $this->assertNull(AuditEngine::rejectUnsafeUrl('http://93.184.216.34/robots.txt'));
        $this->assertNull(AuditEngine::rejectUnsafeUrl('https://93.184.216.34/'));
    }

    public function test_robots_txt_tool_rejects_private_target_without_request(): void
    {
        $result = (new SeoCheckTools)->robotsTxt(['url' => 'http://127.0.0.1:8888/robots.txt']);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('不允许抓取', (string) $result['error']);
    }

    public function test_duplicate_content_tool_rejects_private_target(): void
    {
        $result = (new SeoCheckTools)->duplicateContent([
            'url_a' => 'http://169.254.169.254/latest/meta-data',
            'url_b' => 'https://example.com/',
        ]);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('不允许抓取', (string) $result['error']);
    }

    public function test_redirect_trace_rejects_private_start(): void
    {
        $result = (new NetworkTools)->redirectTrace(['url' => 'http://10.0.0.1/']);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('不允许抓取', (string) $result['error']);
    }

    public function test_plaintext_email_tool_rejects_private_target(): void
    {
        $result = (new DevTools)->plaintextEmail(['url' => 'http://127.0.0.1/']);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('不允许抓取', (string) $result['error']);
    }
}
