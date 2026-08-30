<x-mail::layout>
    <x-mail::header>
        {{ config('app.name') }}
    </x-mail::header>

    <x-mail::message>
        {!! $content !!}
    </x-mail::message>

    <x-mail::footer>
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </x-mail::footer>
</x-mail::layout>
