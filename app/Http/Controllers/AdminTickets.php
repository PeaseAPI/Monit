<?php

namespace App\Http\Controllers;

use App\Mail\TicketRepliedToUser;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Support\Typed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * 管理后台 - 工单处理（A4：列表 / 详情 / 回复 / 状态）
 */
class AdminTickets extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $status = in_array($status, ['0', '1', '2'], true) ? (int) $status : null;

        $tickets = Ticket::with('user')
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->orderByRaw('CASE status WHEN 0 THEN 0 WHEN 1 THEN 1 ELSE 2 END')
            ->orderByDesc('last_reply_at')
            ->paginate(30);

        $counts = [
            'all' => Ticket::count(),
            'open' => Ticket::where('status', Ticket::STATUS_OPEN)->count(),
            'answered' => Ticket::where('status', Ticket::STATUS_ANSWERED)->count(),
            'closed' => Ticket::where('status', Ticket::STATUS_CLOSED)->count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'status', 'counts'));
    }

    public function show(int $ticketId): View
    {
        $ticket = Ticket::with(['replies.user', 'user'])->findOrFail($ticketId);

        return view('admin.tickets.show', compact('ticket'))->with('adminNav', 'tickets');
    }

    public function reply(Request $request, int $ticketId): RedirectResponse
    {
        $validated = Typed::arr($request->validate(['message' => ['required', 'string', 'max:20000']]));

        $ticket = Ticket::findOrFail($ticketId);

        // 回复落库与工单状态置 ANSWERED 同事务
        $reply = DB::transaction(function () use ($ticket, $validated): TicketReply {
            $reply = TicketReply::create([
                'ticket_id' => $ticket->ticket_id,
                'user_id' => $this->user()->user_id,
                'is_staff' => true,
                'message' => $validated['message'],
                'via' => 'web',
                'datetime' => now(),
            ]);

            $ticket->update(['status' => Ticket::STATUS_ANSWERED, 'last_reply_at' => now()]);

            return $reply;
        });

        // 通知提交人（登录用户或游客邮箱）：登录查看详情 / 游客邮件往来
        try {
            Mail::to($ticket->email)->send(new TicketRepliedToUser($ticket, $reply));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', __('msg.ticket_reply_sent'));
    }

    public function updateStatus(Request $request, int $ticketId): RedirectResponse
    {
        $validated = Typed::arr($request->validate(['status' => ['required', 'in:0,1,2']]));

        Ticket::findOrFail($ticketId)->update(['status' => Typed::int($validated['status'])]);

        return back()->with('success', __('msg.ticket_status_updated'));
    }

    public function destroy(int $ticketId): RedirectResponse
    {
        $ticket = Ticket::findOrFail($ticketId);

        // 回复与工单同事务删除，避免留下孤儿回复
        DB::transaction(function () use ($ticket): void {
            $ticket->replies()->delete();
            $ticket->delete();
        });

        return redirect()->route('admin.tickets.index')->with('success', __('msg.ticket_deleted'));
    }
}
