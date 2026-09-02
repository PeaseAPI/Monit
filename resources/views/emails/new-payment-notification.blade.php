<x-mail::layout>
    <x-mail::header :url="config('app.url')">
        {{ config('app.name') }} - Admin
    </x-mail::header>

    <x-mail::message>
        {{ __('msg.new_payment_notification_body') }}

        **{{ __('msg.user') }}**: {{ $payment->user_id }}
        **{{ __('msg.plan') }}**: {{ $payment->plan_id }}
        **{{ __('msg.amount') }}**: {{ $payment->total_amount }} {{ $payment->currency }}
        **{{ __('msg.processor') }}**: {{ $payment->payment_processor }}
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
