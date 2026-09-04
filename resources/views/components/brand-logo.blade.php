{{-- 品牌标识组件（M23 品牌可控）：优先设置里的 logo 图片，否则回退首字母方块
     用法：<x-brand-logo dark class="h-9 w-9" text-class="text-lg" /> --}}
@props([
    'dark' => false,
    'href' => null,
    'class' => 'h-9 w-9',
    'textClass' => 'text-lg',
    'showText' => true,
])
@php
    $logo = \App\Support\Brand::logoUrl($dark);
    $name = \App\Support\Brand::name();
    $tag = $href ? 'a' : 'span';
@endphp
<{{ $tag }}@if ($href) href="{{ $href }}"@endif class="inline-flex items-center gap-2.5">
    @if ($logo)
        <img src="{{ $logo }}" alt="{{ $name }}" class="{{ $class }} object-contain">
    @else
        <span class="flex {{ $class }} items-center justify-center rounded-xl bg-gradient-to-br {{ $dark ? 'from-brand-400 to-brand-600' : 'from-brand-500 to-brand-700' }} text-base font-bold text-white">{{ mb_substr($name, 0, 1) }}</span>
    @endif
    @if ($showText)
        <span class="{{ $textClass }} font-semibold">{{ $name }}</span>
    @endif
</{{ $tag }}>
