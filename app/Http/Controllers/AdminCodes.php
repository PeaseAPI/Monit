<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Models\Plan;
use App\Support\Typed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 管理后台 - 兑换码管理 + 已兑换记录
 * 规格书 §6.3.3 / §10.3 / 附B：AdminCodes / AdminCodeCreate / AdminCodeUpdate / AdminRedeemedCodes
 */
class AdminCodes extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        $codes = Code::withCount('redeemedCodes')->orderByDesc('code_id')->paginate(25);

        return view('admin.codes.index', compact('codes'))->with('adminNav', 'codes');
    }

    /**
     * @return View
     */
    public function create()
    {
        $plans = Plan::where('is_enabled', true)->orderBy('order')->get();

        return view('admin.codes.form', ['code' => new Code, 'plans' => $plans, 'codeValue' => Str::upper(Str::random(16))])->with('adminNav', 'codes');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        // 批量创建：quantity > 1 时忽略手工码，逐个生成唯一随机码（用户反馈 #40-6）
        $quantity = (int) $request->input('quantity', 1);
        $quantity = ($quantity >= 1 && $quantity <= 100) ? $quantity : 1;
        $batch = $quantity > 1;

        for ($i = 0; $i < $quantity; $i++) {
            $attributes = $validated;
            $attributes['code'] = $batch
                ? $this->uniqueCode()
                : ($validated['code'] ?? Str::upper(Str::random(16)));
            $attributes['datetime'] = now();

            Code::create($attributes);
        }

        $message = $batch
            ? trans('msg.code_created_batch', ['count' => $quantity])
            : __('msg.code_created');

        return redirect()->route('admin.codes.index')
            ->with('success', $message);
    }

    /**
     * 生成全局唯一的随机兑换码（16 位大写字母数字；唯一索引的双保险）
     */
    protected function uniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(16));
        } while (Code::where('code', $code)->exists());

        return $code;
    }

    /**
     * @return View
     */
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
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $validated = Typed::arr($request->validate([
            'name' => ['required', 'string', 'max:256'],
            'code' => ['nullable', 'string', 'max:64', Rule::unique('codes', 'code')->ignore($request->route('codeId'), 'code_id')],
            'type' => ['required', 'in:plan,discount'],
            'plan_id' => ['nullable', 'required_if:type,plan', 'string', 'max:64'],
            'days' => ['nullable', 'integer', 'min:0'],
            'discount' => ['nullable', 'numeric', 'between:0,100'],
            'max_redemptions' => ['nullable', 'integer', 'min:0'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'is_enabled' => ['boolean'],
        ]));

        $validated['is_enabled'] = $request->boolean('is_enabled', true);

        if ($validated['type'] !== 'plan') {
            $validated['plan_id'] = null;
        }

        return $validated;
    }
}
