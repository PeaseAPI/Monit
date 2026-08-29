<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Models\Plan;
use App\Models\RedeemedCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 管理后台 - 兑换码管理 + 已兑换记录
 * 规格书 §6.3.3 / §10.3 / 附B：AdminCodes / AdminCodeCreate / AdminCodeUpdate / AdminRedeemedCodes
 */
class AdminCodes extends Controller
{
    public function index()
    {
        $codes = Code::withCount('redeemedCodes')->orderByDesc('code_id')->paginate(25);

        return view('admin.codes.index', compact('codes'))->with('adminNav', 'codes');
    }

    public function create()
    {
        $plans = Plan::where('is_enabled', true)->orderBy('order')->get();

        return view('admin.codes.form', ['code' => new Code(), 'plans' => $plans, 'codeValue' => Str::upper(Str::random(16))])->with('adminNav', 'codes');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['code'] = $validated['code'] ?: Str::upper(Str::random(16));

        Code::create([...$validated, 'datetime' => now()]);

        return redirect()->route('admin.codes.index')
                        ->with('success', __('msg.code_created'));
    }

    public function edit(int $codeId)
    {
        $code = Code::findOrFail($codeId);
        $plans = Plan::where('is_enabled', true)->orderBy('order')->get();

        return view('admin.codes.form', compact('code', 'plans'))->with('adminNav', 'codes');
    }

    public function update(Request $request, int $codeId): RedirectResponse
    {
        $code = Code::findOrFail($codeId);
        $code->update($this->validated($request));

        return redirect()->route('admin.codes.index')
                        ->with('success', __('msg.code_updated'));
    }

    public function destroy(int $codeId): RedirectResponse
    {
        Code::findOrFail($codeId)->delete();

        return redirect()->route('admin.codes.index')
                        ->with('success', __('msg.code_deleted'));
    }

    /**
     * 已兑换记录（AdminRedeemedCodes）
     */
    public function redeemed()
    {
        $redeemed = RedeemedCode::with(['user', 'code'])->orderByDesc('redeemed_id')->paginate(25);

        return view('admin.codes.redeemed', compact('redeemed'))->with('adminNav', 'codes');
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'code' => ['nullable', 'string', 'max:64'],
            'type' => ['required', 'in:plan,discount'],
            'plan_id' => ['nullable', 'required_if:type,plan', 'string', 'max:64'],
            'days' => ['nullable', 'integer', 'min:0'],
            'discount' => ['nullable', 'numeric', 'between:0,100'],
            'max_redemptions' => ['nullable', 'integer', 'min:0'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'is_enabled' => ['boolean'],
        ]);

        $validated['is_enabled'] = $request->boolean('is_enabled', true);

        if ($validated['type'] !== 'plan') {
            $validated['plan_id'] = null;
        }

        return $validated;
    }
}
