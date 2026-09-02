<x-mail::layout>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        # {{ __('msg.plan_downgraded_title') }}

        {{ __('msg.plan_downgraded_greeting', ['name' => $user->name]) }},

        {{ __('msg.plan_downgraded_body') }}

        <x-mail::button :url="route('account.plan')">
            {{ __('msg.view_plans') }}
        </x-mail::button>

        {{ __('msg.plan_downgraded_footer') }}
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
