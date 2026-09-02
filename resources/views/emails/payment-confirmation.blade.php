<x-mail::layout>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.payment_confirmation_body') }}

        **{{ __('msg.plan') }}**: {{ $payment->plan_id }}
        **{{ __('msg.amount') }}**: {{ $payment->total_amount }} {{ $payment->currency }}
        **{{ __('msg.date') }}**: {{ $payment->datetime }}
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
