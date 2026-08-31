<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Support\Settings;

/**
 * 管理后台 - 发票 / 信用票据（红冲）
 * 规格书 §6.3.3 / 附B：AdminInvoice、AdminCreditNotes
 *
 * 以 payment 记录为源生成可打印 HTML 发票（浏览器打印 -> PDF），
 * 票号规则：发票 {business.invoice_nr_prefix|INV-}{payment_id}，信用票据 CN-{payment_id}。
 * 抬头信息：business 组（后台「发票信息」）优先，回退 main 站点设置。
 */
class AdminInvoice extends Controller
{
    public function invoice(int $paymentId)
    {
        $payment = Payment::with('user')->findOrFail($paymentId);

        return view('admin.payments.invoice', [
            'payment' => $payment,
            'mode' => 'invoice',
            'documentNo' => Settings::get('business.invoice_nr_prefix', 'INV-').str_pad((string) $payment->payment_id, 6, '0', STR_PAD_LEFT),
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

    /**
     * 开票方信息：business 组（后台「发票信息」）优先，回退站点常规设置
     */
    private function companyInfo(): array
    {
        $addressParts = array_filter([
            Settings::get('business.country'),
            Settings::get('business.city'),
            Settings::get('business.county'),
            Settings::get('business.zip'),
            Settings::get('business.address'),
        ], fn ($part) => is_string($part) && $part !== '');

        $taxType = Settings::get('business.tax_type');
        $taxId = Settings::get('business.tax_id');

        return [
            'name' => Settings::get('business.brand_name') ?: Settings::get('main.title', 'Monit'),
            'legalName' => Settings::get('business.name'),
            'email' => Settings::get('business.email') ?: Settings::get('main.contact_email', config('mail.from.address')),
            'phone' => Settings::get('business.phone'),
            'address' => implode(' ', $addressParts),
            'taxType' => $taxType,
            'taxId' => $taxType && $taxId ? $taxType.': '.$taxId : $taxId,
            'url' => config('app.url'),
        ];
    }

    /**
     * 信用票据列表（规格书 §6.3.3：AdminCreditNotes）
     */
    public function creditNotesIndex()
    {
        $creditNotes = Payment::with('user')
            ->where('type', 'refund')
            ->orWhere('total_amount', '<', 0)
            ->orderByDesc('datetime')
            ->paginate(25);

        return view('admin.credit-notes.index', compact('creditNotes'))->with('adminNav', 'payments');
    }
}
