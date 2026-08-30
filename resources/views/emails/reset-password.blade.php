<x-mail::layout>
    <x-mail::header>
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.reset_password_body') }}

        <x-mail::button :url="$resetUrl">
            {{ __('msg.reset_password_button') }}
        </x-mail::button>

        {{ __('msg.reset_password_footer') }}
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
