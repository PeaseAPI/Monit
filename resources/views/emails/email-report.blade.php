<x-mail::layout>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.email_report_body', ['name' => $website->name]) }}

        @foreach($stats as $key => $value)
        **{{ __($key) }}**: {{ $value }}
        @endforeach

        <x-mail::button :url="route('stats.overview', $website->pixel_key)">
            {{ __('msg.view_statistics') }}
        </x-mail::button>
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
