<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Support\Settings;

/**
 * 管理后台 - 发票 / 信用票据（红冲）
 * 规格书 §6.3.3 / 附B：AdminInvoice、AdminCreditNotes
 *
 * 以 payment 记录为源生成可打印 HTML 发票（浏览器打印 -> PDF），
 * 票号规则：发票 INV-{payment_id}，信用票据 CN-{payment_id}。
 */
class AdminInvoice extends Controller
{
    public function invoice(int $paymentId)
    {
        $payment = Payment::with('user')->findOrFail($paymentId);

        return view('admin.payments.invoice', [
            'payment' => $payment,
            'mode' => 'invoice',
            'documentNo' => 'INV-'.str_pad((string) $payment->payment_id, 6, '0', STR_PAD_LEFT),
            'company' => $this->companyInfo(),
        ]);
    }

    public function creditNote(int $paymentId)
    {
        $payment = Payment::with('user')->findOrFail($paymentId);

        return view('admin.payments.invoice', [
            'payment' => $payment,
            'mode' => 'credit_note',
            'documentNo' => 'CN-'.str_pad((string) $payment->payment_id, 6, '0', STR_PAD_LEFT),
            'company' => $this->companyInfo(),
        ]);
    }

    private function companyInfo(): array
    {
        return [
            'name' => Settings::get('main.title', 'Monit'),
            'email' => Settings::get('main.contact_email', config('mail.from.address')),
            'url' => config('app.url'),
        ];
    }

    /**
     * 信用票据列表（规格书 §6.3.3：AdminCreditNotes）
     */
    public function creditNotesIndex()
    {
        $creditNotes = Payment::with('user')
            ->where('payment_type', 'refund')
            ->orWhere('total_amount', '<', 0)
            ->orderByDesc('datetime')
            ->paginate(25);

        return view('admin.credit-notes.index', compact('creditNotes'))->with('adminNav', 'payments');
    }
}
