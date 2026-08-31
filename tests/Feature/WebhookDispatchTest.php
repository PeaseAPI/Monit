<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentService;
use App\Services\WebhookService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * M15.1 平台 Webhook 派发（规格 §6.3.1：webhooks 设置组）
 */
class WebhookDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Settings::flush();
        Http::fake();
    }

    #[Test]
    public function dispatches_to_configured_url(): void
    {
        Settings::set('webhooks.webhook_payment_success_url', 'https://example.com/hook');

        app(WebhookService::class)->paymentSuccess(['payment_id' => 1, 'amount' => 9.99]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/hook'
                && $request['event'] === 'payment_success'
                && $request['payload']['amount'] === 9.99;
        });
    }

    #[Test]
    public function no_dispatch_without_url(): void
    {
        app(WebhookService::class)->userRegister(['user_id' => 1]);

        Http::assertNothingSent();
    }

    #[Test]
    public function no_dispatch_for_invalid_url(): void
    {
        Settings::set('webhooks.webhook_user_register_url', 'not-a-url');

        app(WebhookService::class)->userRegister(['user_id' => 1]);

        Http::assertNothingSent();
    }

    #[Test]
    public function unknown_event_is_ignored(): void
    {
        Settings::set('webhooks.webhook_bogus_url', 'https://example.com/x');

        app(WebhookService::class)->dispatch('bogus', []);

        Http::assertNothingSent();
    }

    #[Test]
    public function dispatch_failure_does_not_throw(): void
    {
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });

        Settings::set('webhooks.webhook_user_delete_url', 'https://example.com/hook');

        app(WebhookService::class)->userDelete(['user_id' => 7]);

        $this->assertTrue(true); // 未抛异常即通过（主流程不被阻塞）
    }

    #[Test]
    public function payment_failure_dispatches_and_marks_payment_failed(): void
    {
        $user = User::create([
            'name' => 'Fail Tester', 'email' => 'fail@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        $payment = Payment::create([
            'user_id' => $user->user_id, 'name' => $user->name, 'email' => $user->email,
            'payment_processor' => 'stripe', 'type' => 'one_time', 'frequency' => 'one_time',
            'status' => 0, 'total_amount' => 19.99, 'currency' => 'USD', 'datetime' => now(),
        ]);
        Settings::set('webhooks.webhook_payment_failure_url', 'https://example.com/fail-hook');

        app(PaymentService::class)->handlePaymentFailure(
            $payment->payment_id, 'pi_ext_123', 'card_declined'
        );

        $this->assertSame(2, (int) $payment->fresh()->status); // 2 = failed
        Http::assertSent(function ($request) use ($payment) {
            return $request->url() === 'https://example.com/fail-hook'
                && $request['event'] === 'payment_failure'
                && $request['payload']['payment_id'] === $payment->payment_id
                && $request['payload']['reason'] === 'card_declined';
        });
    }

    #[Test]
    public function paid_payment_is_never_marked_failed(): void
    {
        $user = User::create([
            'name' => 'Paid Tester', 'email' => 'paid@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        $payment = Payment::create([
            'user_id' => $user->user_id, 'name' => $user->name, 'email' => $user->email,
            'payment_processor' => 'stripe', 'type' => 'one_time', 'frequency' => 'one_time',
            'status' => 1, 'total_amount' => 19.99, 'currency' => 'USD', 'datetime' => now(),
        ]);
        Settings::set('webhooks.webhook_payment_failure_url', 'https://example.com/fail-hook');

        app(PaymentService::class)->handlePaymentFailure($payment->payment_id, 'pi_ext_123', 'late webhook');

        $this->assertSame(1, (int) $payment->fresh()->status); // 仍为已支付
        Http::assertNothingSent(); // 不派发
    }
}
