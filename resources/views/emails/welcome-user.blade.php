<x-mail::layout>
    <x-mail::header :url="config('app.url')">
        {{ $siteName }}
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.hello') }} {{ $user->name }},

        {{ __('msg.welcome_email_body', ['site' => $siteName]) }}
    </x-mail::message>

    <x-mail::button :url="$loginUrl">
        {{ __('msg.welcome_email_login') }}
    </x-mail::button>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ $siteName }}
    </x-mail::footer>
</x-mail::layout>
