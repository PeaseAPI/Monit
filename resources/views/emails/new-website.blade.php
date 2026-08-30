<x-mail::layout>
    <x-mail::header>
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        # {{ __('msg.new_website_title') }}

        {{ __('msg.new_website_body') }}

        <x-mail::panel>
            <table>
                <tr><td>{{ __('msg.website_name') }}:</td><td>{{ $websiteName }}</td></tr>
                <tr><td>{{ __('msg.website_host') }}:</td><td>{{ $websiteHost }}</td></tr>
                <tr><td>{{ __('msg.user_name') }}:</td><td>{{ $userName }}</td></tr>
                <tr><td>{{ __('msg.user_email') }}:</td><td>{{ $userEmail }}</td></tr>
            </table>
        </x-mail::panel>

        <x-mail::button :url="route('admin.websites.index')">
            {{ __('msg.view_in_admin') }}
        </x-mail::button>
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
