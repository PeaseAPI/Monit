@extends('layouts.guest')

@section('title', __('stats.public_auth_title') . ' - ' . $website->name)

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                {{ $website->name }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ __('stats.public_auth_desc') }}
            </p>
        </div>

        <form class="mt-8 space-y-6" method="POST" action="{{ route('statistics.public.auth', ['pixel_key' => $website->pixel_key]) }}">
            @csrf

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">{{ __('auth.password') }}</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    {{ __('auth.view_statistics') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection