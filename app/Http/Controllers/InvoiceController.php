<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Barry\DomPDF\Facade\Pdf;

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
        $payments = Payment::where('user_id', auth()->id())
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
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
            'invoice_number' => 'INV-' . str_pad($payment->payment_id, 6, '0', STR_PAD_LEFT),
            'date' => $payment->created_at->format('Y-m-d'),
            'user' => $user,
            'payment' => $payment,
            'plan' => $plan,
            'billing' => $billing,
            'site_title' => \App\Models\Setting::where('key', 'main.site_title')->value('value') ?? config('app.name'),
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
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('invoices.credit_notes', compact('creditNotes'));
    }
}
