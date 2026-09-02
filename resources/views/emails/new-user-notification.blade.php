<x-mail::layout>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }} - Admin
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.new_user_notification_body', ['name' => $newUser->name, 'email' => $newUser->email]) }}

        <x-mail::button :url="route('admin.users.edit', $newUser->user_id)">
            {{ __('msg.view_user') }}
        </x-mail::button>
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
