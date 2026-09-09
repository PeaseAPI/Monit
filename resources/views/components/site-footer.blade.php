{{-- 全站统一页脚组件（x-site-footer）：深色多栏 + 版权 + ICP 备案 --}}
<footer class="bg-zinc-950 text-zinc-400">
    <div class="mx-auto max-w-7xl px-6 pt-16 pb-8">
        <div class="mb-14 h-px rounded-full bg-gradient-to-r from-transparent via-zinc-700 to-transparent"></div>
        <div class="grid gap-10 md:grid-cols-5">
            <div class="md:col-span-2">
                <x-brand-logo dark />
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-zinc-500">{{ __('landing.subtitle') }}</p>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-zinc-200">{{ __('landing.footer_product') }}</h4>
                <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                    <li><a href="{{ route('index') }}#features" class="transition hover:text-white">{{ __('landing.nav_features') }}</a></li>
                    <li><a href="{{ route('index') }}#pricing" class="transition hover:text-white">{{ __('landing.nav_pricing') }}</a></li>
                    <li><a href="{{ route('api.docs') }}" class="transition hover:text-white">{{ __('landing.footer_api') }}</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-zinc-200">{{ __('landing.footer_resources') }}</h4>
                <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                    <li><a href="{{ route('blog') }}" class="transition hover:text-white">{{ __('landing.nav_blog') }}</a></li>
                    <li><a href="{{ route('help') }}" class="transition hover:text-white">{{ __('landing.nav_help') }}</a></li>
                    <li><a href="{{ route('contact') }}" class="transition hover:text-white">{{ __('landing.footer_contact') }}</a></li>
                    @if (\App\Support\Settings::get('seo.tools_is_enabled', true)
                        && (auth()->check() || in_array(\App\Support\Settings::get('seo.tools_guest_access'), [true, 'true', '1'], true)))
                    <li><a href="{{ route('seo.tools') }}" class="transition hover:text-white">{{ __('landing.nav_seo_tools') }}</a></li>
                    @endif
                    @if (filter_var(\App\Support\Settings::get('seo.audits_is_enabled', true), FILTER_VALIDATE_BOOLEAN))
                    <li><a href="{{ route('seo.directory') }}" class="transition hover:text-white">{{ __('landing.nav_seo_directory') }}</a></li>
                    @endif
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-zinc-200">{{ __('landing.footer_legal') }}</h4>
                <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                    {{-- 法务链接（main.terms_and_conditions_url / privacy_policy_url）：外链优先，站内静态页兜底 --}}
                    @php($termsUrl = trim((string) \App\Support\Settings::get('main.terms_and_conditions_url', '')))
                    @php($privacyUrl = trim((string) \App\Support\Settings::get('main.privacy_policy_url', '')))
                    <li><a href="{{ $termsUrl !== '' ? $termsUrl : route('terms') }}"@if ($termsUrl !== '') target="_blank" rel="noopener"@endif class="transition hover:text-white">{{ __('landing.footer_terms') }}</a></li>
                    <li><a href="{{ $privacyUrl !== '' ? $privacyUrl : route('privacy') }}"@if ($privacyUrl !== '') target="_blank" rel="noopener"@endif class="transition hover:text-white">{{ __('landing.footer_privacy') }}</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-14 border-t border-zinc-800/80 pt-8 text-center">
            <p class="text-sm text-zinc-500">© {{ date('Y') }} {{ \App\Support\Brand::name() }}. {{ __('landing.footer_rights') }}</p>
            {{-- ICP 备案号（后台 设置 → 品牌 → 页脚备案号） --}}
            @if ($icp = \App\Support\Brand::icp())
            <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer nofollow" class="mt-2 inline-block text-sm text-zinc-600 transition hover:text-zinc-400">{{ $icp }}</a>
            @endif
        </div>
    </div>
</footer>
