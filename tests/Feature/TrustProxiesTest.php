<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TrustProxies 接线回归测试（.env TRUSTED_PROXIES / config monit.trusted_proxies）
 *
 * 背景：trustProxies(at: env(...)) 不能放在 bootstrap/app.php 的 withMiddleware
 * 闭包——该闭包经 afterResolving(HttpKernel) 在 kernel 构造期执行，早于
 * .env/config 加载，env() 永远只能拿到默认值；正确位置是 provider boot
 *（AppServiceProvider::boot：.env 已加载、TrustProxies 中间件未执行）。
 *
 * 观测手段：pixel-track 的负向限流键 pixel.miss:{ip} 直接反映 request->ip()。
 */
class TrustProxiesTest extends TestCase
{
    use RefreshDatabase;

    /** 读取 TrustProxies 静态配置（框架未提供 getter） */
    protected function trustedProxies(): mixed
    {
        $rp = new \ReflectionProperty(TrustProxies::class, 'alwaysTrustProxies');
        $rp->setAccessible(true);

        return $rp->getValue();
    }

    /** 以指定配置重新执行 provider boot（等价于该配置下的正常启动） */
    protected function rebootWith(string $value): void
    {
        config(['monit.trusted_proxies' => $value]);
        (new AppServiceProvider($this->app))->boot();
    }

    /** 发一次随机 pixel_key 请求（必 miss），返回产生的限流键后缀 IP */
    protected function missRequestIp(): string
    {
        RateLimiter::clear('pixel.miss:8.8.8.8');
        RateLimiter::clear('pixel.miss:127.0.0.1');

        $this->post('/pixel-track/'.uniqid('zz', true), ['data' => ['type' => 'pageview']], [
            'X-Forwarded-For' => '8.8.8.8',
        ])->assertStatus(204);

        if (RateLimiter::attempts('pixel.miss:8.8.8.8') > 0) {
            return '8.8.8.8';
        }

        return RateLimiter::attempts('pixel.miss:127.0.0.1') > 0 ? '127.0.0.1' : 'none';
    }

    public function test_private_默认信任本机与私网段(): void
    {
        $this->rebootWith('private');

        $this->assertSame(
            ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'fc00::/7'],
            $this->trustedProxies()
        );
    }

    public function test_none_不信任任何代理(): void
    {
        $this->rebootWith('none');

        $this->assertSame([], $this->trustedProxies());
    }

    public function test_星号_全部信任(): void
    {
        $this->rebootWith('*');

        $this->assertSame('*', $this->trustedProxies());
    }

    public function test_逗号分隔_CIDR_列表(): void
    {
        $this->rebootWith('10.0.0.0/8, 192.168.1.1 ,172.16.0.0/12');

        $this->assertSame(
            ['10.0.0.0/8', '192.168.1.1', '172.16.0.0/12'],
            $this->trustedProxies()
        );
    }

    public function test_默认配置下可信来源_XFF_生效(): void
    {
        // 测试客户端 REMOTE_ADDR=127.0.0.1 属 private 可信代理 → XFF 解析为客户端 IP
        $this->assertSame('8.8.8.8', $this->missRequestIp());
    }

    public function test_none_时伪造_XFF_被忽略(): void
    {
        $this->rebootWith('none');

        $this->assertSame('127.0.0.1', $this->missRequestIp());
    }
}
