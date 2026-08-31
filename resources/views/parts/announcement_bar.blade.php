{{-- 公告条（原版 announcements）：面向访客/用户的全站顶部公告
     设置：后台 → 设置 → 公告（announcements 组）
     guests/users 独立配置存在时优先生效（原版行为） --}}
@php
    $annEnabled = filter_var(\App\Support\Settings::get('announcements.announcements_is_enabled', false), FILTER_VALIDATE_BOOLEAN);
    $annType = \App\Support\Settings::get('announcements.announcements_type', 'all');
    $annContent = \App\Support\Settings::get('announcements.announcements_content', '');
    $annColor = '#4f46e5';
    $annBg = '#eef2ff';
    $isGuest = auth()->check() === false;
    $audience = $isGuest ? 'guests' : 'users';

    // 原版：audience 粒度过滤
    if ($annType === 'guests' && ! $isGuest) { $annEnabled = false; }
    if ($annType === 'users' && $isGuest) { $annEnabled = false; }

    // 原版：audience 独立配置覆盖
    $audEnabledKey = 'announcements.announcements_' . $audience . '_is_enabled';
    $audContentKey = 'announcements.announcements_' . $audience . '_content';
    if (\App\Support\Settings::get($audEnabledKey) !== null) {
        $annEnabled = filter_var(\App\Support\Settings::get($audEnabledKey, false), FILTER_VALIDATE_BOOLEAN);
        $audContent = \App\Support\Settings::get($audContentKey);
        if ($audContent !== null && $audContent !== '') {
            $annContent = $audContent;
        }
        $audColor = \App\Support\Settings::get('announcements.announcements_' . $audience . '_text_color');
        $audBg = \App\Support\Settings::get('announcements.announcements_' . $audience . '_background_color');
        if ($audColor) { $annColor = $audColor; }
        if ($audBg) { $annBg = $audBg; }
    }
@endphp
@if ($annEnabled && $annContent)
<div id="monit-announcement-bar" style="background-color: {{ $annBg }}; color: {{ $annColor }}">
    <div class="mx-auto flex max-w-7xl items-center justify-center gap-2 px-6 py-2.5 text-sm">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.097.28.28.52.506.697a2 2 0 002.22 0 2 2 0 00.507-.697m-.985-11.19c-.253-.962-.584-1.892-.985-2.783-.097-.28-.28-.52-.506-.697a2 2 0 00-2.22 0 2 2 0 00-.507.697m.985 11.18a41.66 41.66 0 011.934 0m2.985-8.28c.253.962.584 1.892.985 2.783.097.28.28.52.506.697M18 6h.75a4.5 4.5 0 010 9h-.75"/></svg>
        <span class="min-w-0">{!! $annContent !!}</span>
        <button type="button" onclick="var el=this.closest('#monit-announcement-bar');el&&el.remove()" class="ml-2 shrink-0 rounded-full p-1 transition hover:bg-black/10" aria-label="关闭">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>
@endif
