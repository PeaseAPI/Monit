<x-mail::layout>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.hello') }} {{ $user->name }},

        {{ __('msg.user_deletion_reminder_body', ['days' => $days]) }}

        <x-mail::button :url="route('login')">
            {{ __('msg.login_now') }}
        </x-mail::button>
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
