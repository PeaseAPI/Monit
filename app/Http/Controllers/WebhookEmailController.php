<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Support\Settings;
use App\Support\TicketNotifications;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 邮件入站 Webhook（A4 工单邮件整合）
 *
 * 用途：邮件服务商/转发脚本（Mailgun route、SendGrid Inbound Parse、
 * 自建 IMAP 轮询脚本等）将新邮件 POST 到本端点，实现「管理员/客户直接回邮件即入工单」。
 *
 * 协议（JSON）：{"from": "a@b.com", "subject": "Re: [#TK-000001] ...", "text": "回复内容"}
 * - subject 匹配 [#TK-xxxx] → 追加为工单回复
 * - 不匹配 → 以发件人邮箱创建新工单
 * 鉴权：?token= 或 X-Webhook-Token 头，与 settings tickets.inbound_webhook_token 比对；
 *       未配置 token 时端点整体 404（fail closed）。
 */
class WebhookEmailController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $expected = trim((string) Settings::get('tickets.inbound_webhook_token', ''));

        if ($expected === '') {
            abort(404);
        }

        $given = (string) ($request->query('token') ?: $request->header('X-Webhook-Token', ''));
        if (! hash_equals($expected, $given)) {
            abort(404);
        }

        $payload = $request->json()->all() ?: $request->post();
        $from = strtolower(trim((string) ($payload['from'] ?? '')));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $text = trim((string) ($payload['text'] ?? ($payload['body'] ?? '')));

        if ($from === '' || $text === '') {
            return response('invalid payload', 422);
        }

        // 剥离引用正文（Outlook/Gmail 常见的 "On ... wrote:" 之前保留全文，由管理员甄别）
        $text = mb_substr($text, 0, 20000);

        if (preg_match('/#TK-(\d{1,6})/i', $subject, $m)) {
            $ticket = Ticket::find((int) $m[1]);

            if ($ticket && $ticket->status !== Ticket::STATUS_CLOSED) {
                $reply = TicketReply::create([
                    'ticket_id' => $ticket->ticket_id,
                    'user_id' => $this->resolveUserId($from) ?? $ticket->user_id,
                    'is_staff' => false,
                    'message' => $text,
                    'via' => 'email',
                    'datetime' => now(),
                ]);

                $ticket->update(['status' => Ticket::STATUS_OPEN, 'last_reply_at' => now()]);

                try {
                    TicketNotifications::notifyAdminsTicketReplied($ticket, $reply);
                } catch (\Throwable $e) {
                    report($e);
                }

                return response('', 204);
            }
        }

        // 新工单（游客邮件直达支持邮箱）
        $ticket = Ticket::create([
            'user_id' => $this->resolveUserId($from),
            'email' => $from,
            'subject' => mb_substr($subject !== '' ? $subject : __('tickets.email_default_subject'), 0, 256),
            'category' => 'general',
            'priority' => 'normal',
            'status' => Ticket::STATUS_OPEN,
            'datetime' => now(),
            'last_reply_at' => now(),
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->ticket_id,
            'user_id' => $ticket->user_id,
            'is_staff' => false,
            'message' => $text,
            'via' => 'email',
            'datetime' => now(),
        ]);

        try {
            TicketNotifications::notifyAdminsTicketCreated($ticket, $text);
        } catch (\Throwable $e) {
            report($e);
        }

        return response('', 204);
    }

    protected function resolveUserId(string $email): ?int
    {
        return User::query()->where('email', $email)->value('user_id');
    }
}
