{{-- 工单对话气泡（用户侧 / 管理侧共用）
    $reply   TicketReply
    $mine    bool  视角己方消息（居右，气泡右上角收角）
    $isStaff bool  客服消息（brand 色气泡 + brand 头像；客户消息白底 + zinc 头像）
    $author  string 显示名
    $initial string 头像首字母 --}}
<div class="flex items-end gap-2.5 {{ $mine ? 'flex-row-reverse' : '' }}">
    <div class="flex h-9 w-9 shrink-0 select-none items-center justify-center rounded-full text-xs font-semibold {{ $isStaff ? 'bg-brand-600 text-white' : 'bg-zinc-800 text-white' }}">{{ $initial }}</div>
    <div class="min-w-0 max-w-[82%] sm:max-w-[75%]">
        <p class="mb-1 flex flex-wrap items-center gap-2 text-xs text-zinc-400 {{ $mine ? 'justify-end' : '' }}">
            <span class="font-medium text-zinc-600">{{ $author }}</span>
            @if ($reply->via === 'email')
            <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-500">email</span>
            @endif
            <span>{{ $reply->datetime->format('Y-m-d H:i') }}</span>
        </p>
        <div class="rounded-2xl border px-4 py-3 text-sm leading-relaxed break-words whitespace-pre-wrap {{ $mine ? 'rounded-tr-sm' : 'rounded-tl-sm' }} {{ $isStaff ? 'border-brand-100 bg-brand-50/70 text-zinc-800' : 'border-zinc-200 bg-white text-zinc-800' }}">{{ $reply->message }}</div>
    </div>
</div>
