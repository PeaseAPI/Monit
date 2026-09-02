<x-mail::layout>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.hello') }} {{ $user->name }},

        {{ __('msg.auto_delete_inactive_users_body', ['days' => $days]) }}

        <x-mail::button :url="route('register')">
            {{ __('msg.register_again') }}
        </x-mail::button>
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
