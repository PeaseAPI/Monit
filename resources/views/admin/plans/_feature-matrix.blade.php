{{--
    套餐功能矩阵局部视图（规格书 §10.2）
    $settings: 现有 plan.settings 数组（create 时为空数组，取默认值）
--}}
@php($features = (array) config('monit.plan_features', []))
<div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($features as $key => $meta)
        @php($type = $meta['type'] ?? 'bool')
        @php($value = array_key_exists($key, $settings) ? $settings[$key] : ($meta['default'] ?? ($type === 'bool' ? false : 0)))
        @php($label = $meta['label'] ?? \Illuminate\Support\Str::headline($key))
        <div class="flex items-center justify-between gap-2 rounded-xl border border-zinc-200 px-3 py-2">
            <label class="text-xs text-zinc-600" @if($type === 'bool') for="feature-{{ $key }}" @endif>
                {{ __("admin.plan_feature.{$key}", [], app()->getLocale()) !== "admin.plan_feature.{$key}" ? __("admin.plan_feature.{$key}") : $label }}
            </label>
            @if($type === 'bool')
                <input id="feature-{{ $key }}" type="checkbox" name="features[{{ $key }}]" value="1"
                       @if((bool) $value) checked @endif class="h-4 w-4 rounded border-zinc-300 text-brand-600">
            @else
                <input type="number" name="features[{{ $key }}]" value="{{ (int) $value }}"
                       class="w-24 rounded-lg border border-zinc-300 px-2 py-1 text-right text-xs">
            @endif
        </div>
    @endforeach
</div>
