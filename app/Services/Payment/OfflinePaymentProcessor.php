<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * 离线支付处理器（银行转账 + 凭证上传）
 * 规格书 §11：Offline Payment
 */
class OfflinePaymentProcessor
{
    /**
     * 创建离线支付记录
     */
    public function createOrder(User $user, Payment $payment): array
    {
        $payment->update([
            'status' => 0, // pending - 等待管理员确认
            'payment_processor' => 'offline',
        ]);

        return [
            'processor' => 'offline',
            'payment_id' => $payment->payment_id,
            'instructions' => config('monit.payment.offline_instructions', __('payment.offline_instructions_default')),
        ];
    }

    /**
     * 上传支付凭证
     */
    public function uploadProof(Request $request, Payment $payment): array
    {
        $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = $request->file('proof')->store("payment-proofs/{$payment->payment_id}", 'local');

        $payment->update([
            'billing' => array_merge($payment->billing ?? [], [
                'proof_path' => $path,
                'proof_uploaded_at' => now()->toIso8601String(),
            ]),
        ]);

        return [
            'success' => true,
            'message' => __('payment.proof_uploaded'),
        ];
    }

    /**
     * 管理员确认离线支付
     */
    public function confirmPayment(Payment $payment): array
    {
        if ($payment->payment_processor !== 'offline') {
            return ['success' => false, 'message' => __('payment.not_offline_payment')];
        }

        if ($payment->status === 1) {
            return ['success' => false, 'message' => __('payment.already_confirmed')];
        }

        $payment->update([
            'status' => 1,
            'last_datetime' => now(),
        ]);

        $paymentService = new PaymentService();
        $paymentService->activatePlan($payment->user, $payment);

        return [
            'success' => true,
            'message' => __('payment.confirmed'),
        ];
    }
}