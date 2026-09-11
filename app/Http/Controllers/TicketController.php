<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Support\TicketNotifications;
use App\Support\Typed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 用户中心 - 我的工单（A4：在线提交 / 查看 / 回复 / 关闭）
 */
class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = $this->user()->tickets()->orderByDesc('ticket_id')->paginate(20);

        return view('tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('tickets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        // 工单与首条回复同事务落库：部分失败会产生无回复的空工单或孤儿回复
        $ticket = DB::transaction(function () use ($validated): Ticket {
            $ticket = Ticket::create([
                'user_id' => $this->user()->user_id,
                'email' => $this->user()->email,
                'subject' => $validated['subject'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'status' => Ticket::STATUS_OPEN,
                'datetime' => now(),
                'last_reply_at' => now(),
            ]);

            TicketReply::create([
                'ticket_id' => $ticket->ticket_id,
                'user_id' => $this->user()->user_id,
                'is_staff' => false,
                'message' => $validated['message'],
                'via' => 'web',
                'datetime' => now(),
            ]);

            return $ticket;
        });

        try {
            TicketNotifications::notifyAdminsTicketCreated($ticket, Typed::string($validated['message']));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('tickets.show', $ticket->ticket_id)
            ->with('success', __('msg.ticket_created'));
    }

    public function show(Request $request, int $ticketId): View
    {
        $ticket = $this->user()->tickets()
            ->with(['replies.user', 'user'])
            ->findOrFail($ticketId);

        return view('tickets.show', compact('ticket'));
    }

    public function reply(Request $request, int $ticketId): RedirectResponse
    {
        $validated = $request->validate(['message' => ['required', 'string', 'max:20000']]);

        $ticket = $this->user()->tickets()->findOrFail($ticketId);

        if ($ticket->status === Ticket::STATUS_CLOSED) {
            return back()->withErrors(['message' => __('msg.ticket_closed_no_reply')]);
        }

        // 回复落库与工单状态回转同事务
        $reply = DB::transaction(function () use ($ticket, $validated): TicketReply {
            $reply = TicketReply::create([
                'ticket_id' => $ticket->ticket_id,
                'user_id' => $this->user()->user_id,
                'is_staff' => false,
                'message' => $validated['message'],
                'via' => 'web',
                'datetime' => now(),
            ]);

            $ticket->update(['status' => Ticket::STATUS_OPEN, 'last_reply_at' => now()]);

            return $reply;
        });

        try {
            TicketNotifications::notifyAdminsTicketReplied($ticket, $reply);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', __('msg.ticket_reply_sent'));
    }

    public function close(Request $request, int $ticketId): RedirectResponse
    {
        $ticket = $this->user()->tickets()->findOrFail($ticketId);
        $ticket->update(['status' => Ticket::STATUS_CLOSED]);

        return back()->with('success', __('msg.ticket_closed'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'subject' => ['required', 'string', 'max:256'],
            'category' => ['required', 'in:'.implode(',', Ticket::CATEGORIES)],
            'priority' => ['required', 'in:'.implode(',', Ticket::PRIORITIES)],
            'message' => ['required', 'string', 'max:20000'],
        ]);
    }
}
