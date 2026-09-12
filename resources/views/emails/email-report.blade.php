<x-mail::layout>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.email_report_body', ['name' => $website->name]) }}

        @php($statLabels = [
            'pageviews' => 'dashboard.pageviews',
            'visitors' => 'dashboard.unique_visitors',
            'sessions' => 'dashboard.sessions',
            'bounce_rate' => 'dashboard.bounce_rate',
            'avg_duration' => 'dashboard.avg_duration',
        ])
        @foreach($stats as $key => $value)
        **{{ __($statLabels[$key] ?? $key) }}**: {{ $key === 'bounce_rate' ? number_format((float) $value, 1).'%' : ($key === 'avg_duration' ? number_format((float) $value).'s' : number_format((float) $value)) }}
        @endforeach

        <x-mail::button :url="route('stats.overview', $website->pixel_key)">
            {{ __('msg.view_statistics') }}
        </x-mail::button>
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
