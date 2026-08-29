{{-- 社交登录按钮（规格书 §12.3：Google + GitHub，仅在配置了凭据时显示） --}}
@php($socialProviders = array_filter(['google' => config('services.google.client_id'), 'github' => config('services.github.client_id')]))
@if($socialProviders)
<div class="mt-6">
    <div class="relative">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-zinc-200"></div></div>
        <div class="relative flex justify-center text-xs"><span class="bg-white px-2 text-zinc-400">{{ __('auth.continue_with') }}</span></div>
    </div>
    <div class="mt-4 flex justify-center gap-3">
        @if(!empty($socialProviders['google']))
        <a href="{{ route('social-login.redirect', 'google') }}" title="Google"
           class="flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-300 transition hover:border-zinc-400 hover:bg-zinc-50">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M23.06 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h6.19a5.3 5.3 0 0 1-2.3 3.47v2.88h3.72c2.18-2 3.45-4.96 3.45-8.36z" fill="#4285F4"/><path d="M12 24c3.12 0 5.74-1.04 7.65-2.81l-3.72-2.88c-1.03.69-2.35 1.1-3.93 1.1a6.87 6.87 0 0 1-6.45-4.75H1.7v2.98A12 12 0 0 0 12 24z" fill="#34A853"/><path d="M5.55 14.66a7.2 7.2 0 0 1 0-4.58V7.1H1.7a12 12 0 0 0 0 10.54l3.85-2.98z" fill="#FBBC05"/><path d="M12 4.75c1.7 0 3.22.59 4.42 1.74l3.3-3.3A11.55 11.55 0 0 0 12 0 12 12 0 0 0 1.7 6.56l3.85 2.98A6.87 6.87 0 0 1 12 4.75z" fill="#EA4335"/></svg>
        </a>
        @endif
        @if(!empty($socialProviders['github']))
        <a href="{{ route('social-login.redirect', 'github') }}" title="GitHub"
           class="flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-300 transition hover:border-zinc-400 hover:bg-zinc-50">
            <svg class="h-5 w-5 fill-current text-zinc-800" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.285 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
        </a>
        @endif
    </div>
</div>
@endif