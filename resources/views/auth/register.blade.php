@extends('layouts.guest')

@section('title', '注册')

@section('content')
    <div class="mb-8 md:hidden">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-xl font-bold text-white">M</span>
    </div>

    <h2 class="text-2xl font-bold">创建账户</h2>
    <p class="mt-2 text-sm text-zinc-500">免费开始，无需信用卡</p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-zinc-700">用户名</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                   placeholder="你的名字"
                   class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('name') border-red-400 @enderror">
            @error('name')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-zinc-700">邮箱地址</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   placeholder="you@example.com"
                   class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('email') border-red-400 @enderror">
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-zinc-700">密码</label>
            <input id="password" type="password" name="password" required
                   placeholder="至少 8 位"
                   class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('password') border-red-400 @enderror">
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
            注册
        </button>
    </form>

    <p class="mt-6 text-sm text-zinc-500">
        已有账户？
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-500">直接登录</a>
    </p>
@endsection
