{{-- 工单通知邮件（通用模板：管理员通知 / 用户回复通知共用） --}}
@component('mail::message')
# {{ $heading }}

**{{ __('msg.ticket_code') }}**: {{ $ticket->code() }}
**{{ __('msg.ticket_subject') }}**: {{ $ticket->subject }}
**{{ __('msg.ticket_category') }}**: {{ __('tickets.category_'.$ticket->category) }} · {{ __('tickets.priority_'.$ticket->priority) }}

{{ __('msg.ticket_original_message') }}:

> {{ $message }}

@isset($actionUrl)
@component('mail::button', ['url' => $actionUrl])
{{ $actionText }}
@endcomponent
@endisset

{{ $footerNote ?? '' }}

{{ __('msg.ticket_footer_signature') }},
{{ config('app.name') }}
@endcomponent
