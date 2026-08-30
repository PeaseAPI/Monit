<x-mail::layout>
    <x-mail::header>
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.hello') }} {{ $user->name }},

        {{ __('msg.plan_limit_notice_body', ['website' => $website->name]) }}

        **{{ __('msg.plan_limit_scene_'.$scene) }}**
        {{ __('msg.plan_limit_notice_quota') }}: {{ number_format($limit) }}
        {{ __('msg.plan_limit_notice_current') }}: {{ number_format($current) }}

        <x-mail::button :url="route('account.plan')">
            {{ __('msg.renew_plan') }}
        </x-mail::button>
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
