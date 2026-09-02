<x-mail::layout>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.hello') }} {{ $user->name }},

        {{ __('msg.activate_account_body') }}

        <x-mail::button :url="$activationUrl">
            {{ __('msg.activate_account_button') }}
        </x-mail::button>

        {{ __('msg.activate_account_footer') }}
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
