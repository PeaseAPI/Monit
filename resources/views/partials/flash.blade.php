@if (session('success'))
<div class="mx-auto mt-4 max-w-7xl px-6">
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
</div>
@endif