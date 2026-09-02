<x-mail::layout>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.hello') }} {{ $user->name }},

        {{ __('msg.plan_expiry_reminder_body', ['plan' => $planName, 'date' => $expirationDate]) }}

        <x-mail::button :url="route('account.plan')">
            {{ __('msg.renew_plan') }}
        </x-mail::button>
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
