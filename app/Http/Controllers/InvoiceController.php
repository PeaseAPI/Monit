<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use Barry\DomPDF\Facade\Pdf;
use Illuminate\View\View;

/**
 * 发票控制器
 * 规格书 §6.2.6：/invoice - 下载发票
 */
class InvoiceController extends Controller
{
    /**
     * 显示发票列表
     */
    public function index(): View
    {
        // 发票开关（payment.invoice_is_enabled，默认开启）
        abort_unless(\App\Http\Controllers\PaymentController::invoiceEnabled(), 404);

        // status 为 tinyint（1=paid）；datetime 为下单时间列（无 created_at 时间戳列）
        $payments = Payment::where('user_id', auth()->id())
            ->where('status', 1)
            ->orderByDesc('datetime')
            ->paginate(15);

        return view('invoices.index', compact('payments'));
    }

    /**
     * 下载发票 PDF
     */
    public function download(Payment $payment)
    {
        // 确保用户只能下载自己的发票
        if ($payment->user_id !== auth()->id()) {
            abort(403);
        }

        $user = auth()->user();
        $plan = Plan::find($payment->plan_id);
        $billing = $user->billing ?? [];

        $data = [
            'invoice_number' => 'INV-'.str_pad($payment->payment_id, 6, '0', STR_PAD_LEFT),
            'date' => $payment->datetime?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'user' => $user,
            'payment' => $payment,
            'plan' => $plan,
            'billing' => $billing,
            'site_title' => Setting::where('key', 'main.site_title')->value('value') ?? config('app.name'),
        ];

        // 简单 HTML 发票（无需 DomPDF 依赖）
        return response()->view('invoices.pdf', $data)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * 信用票据列表
     */
    public function creditNotes(): View
    {
        $creditNotes = Payment::where('user_id', auth()->id())
            ->where('type', 'refund')
            ->orderByDesc('datetime')
            ->paginate(15);

        return view('invoices.credit_notes', compact('creditNotes'));
    }
}
