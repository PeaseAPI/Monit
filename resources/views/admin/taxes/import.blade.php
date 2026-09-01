@extends('layouts.admin')
@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.taxes.index') }}" class="text-zinc-400 hover:text-zinc-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        </a>
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.taxes_import_title') }}</h1>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm text-zinc-500 mb-4">{{ __('admin.taxes_import_desc') }}</p>

        <div class="mb-4 rounded-xl bg-zinc-50 p-4 text-xs font-mono text-zinc-600">
            name,description,value,value_type,type,billing_type,countries<br>
            VAT Standard,标准增值税,20,percentage,exclusive,personal,["CN","US"]<br>
            GST,商品服务税,10,percentage,inclusive,business,["AU"]
        </div>

        <form method="POST" action="{{ route('admin.taxes.import.submit') }}" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 mb-1">{{ __('admin.csv_file') }}</label>
                    <input type="file" name="file" accept=".csv,.txt" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                </div>
                <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">
                    {{ __('admin.import') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection