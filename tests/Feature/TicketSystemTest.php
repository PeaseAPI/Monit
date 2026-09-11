<?php

namespace Tests\Feature;

use App\Mail\TicketRepliedToUser;
use App\Mail\TicketSubmittedToAdmin;
use App\Mail\TicketUserRepliedToAdmin;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * A4：工单系统 —— 在线提交 / 回复 / 关闭 / 邮件通知 / 邮件入站 Webhook / 权限
 */
class TicketSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => '工单用户', 'email' => uniqid('tk-').'@ticket.test',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ], $attrs));
    }

    protected function makeAdmin(): User
    {
        return $this->makeUser(['type' => 1, 'email' => 'tk-admin@ticket.test']);
    }

    protected function makeTicket(User $user): Ticket
    {
        $ticket = Ticket::create([
            'user_id' => $user->user_id, 'email' => $user->email,
            'subject' => '统计代码不生效', 'category' => 'technical', 'priority' => 'normal',
            'status' => Ticket::STATUS_OPEN, 'datetime' => now(), 'last_reply_at' => now(),
        ]);
        TicketReply::create([
            'ticket_id' => $ticket->ticket_id, 'user_id' => $user->user_id,
            'is_staff' => false, 'message' => '安装后看不到数据', 'via' => 'web', 'datetime' => now(),
        ]);

        return $ticket;
    }

    public function test_user_can_submit_ticket_and_admins_get_email(): void
    {
        Mail::fake();
        $this->makeAdmin();

        $this->actingAs($this->makeUser())->post('/tickets', [
            'subject' => '如何接入微信支付', 'category' => 'billing', 'priority' => 'high',
            'message' => '请指导配置微信支付参数。',
        ])->assertRedirect();

        $ticket = Ticket::where('subject', '如何接入微信支付')->firstOrFail();
        $this->assertSame(Ticket::STATUS_OPEN, $ticket->status);
        $this->assertSame(1, $ticket->replies()->count());
        Mail::assertSent(TicketSubmittedToAdmin::class);
    }

    public function test_admin_reply_emails_the_customer_and_sets_answered(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $ticket = $this->makeTicket($this->makeUser());

        $this->actingAs($admin)->post('/admin/tickets/'.$ticket->ticket_id.'/reply', [
            'message' => '已为您检查，请在网站 head 标签中粘贴脚本。',
        ])->assertSessionHas('success');

        $this->assertSame(Ticket::STATUS_ANSWERED, $this->freshModel($ticket)->status);
        Mail::assertSent(TicketRepliedToUser::class, fn (TicketRepliedToUser $mail) => $mail->ticket->ticket_id === $ticket->ticket_id);
    }

    public function test_user_reply_notifies_admins_and_reopens_ticket(): void
    {
        Mail::fake();
        $this->makeAdmin();
        $ticket = $this->makeTicket($this->makeUser());

        $ticket->update(['status' => Ticket::STATUS_ANSWERED]);

        $ticketUser = $ticket->user;
        $this->assertNotNull($ticketUser);
        $this->actingAs($ticketUser)->post('/tickets/'.$ticket->ticket_id.'/reply', [
            'message' => '仍然没有数据，请再帮忙看看。',
        ])->assertSessionHas('success');

        $this->assertSame(Ticket::STATUS_OPEN, $this->freshModel($ticket)->status);
        Mail::assertSent(TicketUserRepliedToAdmin::class);
    }

    public function test_user_cannot_view_others_ticket(): void
    {
        $other = $this->makeUser(['email' => 'other@ticket.test']);
        $ticket = $this->makeTicket($other);

        $this->actingAs($this->makeUser(['email' => 'viewer@ticket.test']))
            ->get('/tickets/'.$ticket->ticket_id)->assertStatus(404);
    }

    public function test_closed_ticket_cannot_be_replied(): void
    {
        $ticket = $this->makeTicket($this->makeUser());

        $ticket->update(['status' => Ticket::STATUS_CLOSED]);

        $ticketUser = $ticket->user;
        $this->assertNotNull($ticketUser);
        $this->actingAs($ticketUser)->post('/tickets/'.$ticket->ticket_id.'/reply', [
            'message' => '再回复一条',
        ])->assertSessionHasErrors('message');
    }

    public function test_email_inbound_webhook_appends_reply_by_ticket_code(): void
    {
        Mail::fake();
        $this->makeAdmin();
        Settings::set('tickets.inbound_webhook_token', 'tok-abc');
        $ticket = $this->makeTicket($this->makeUser());

        // 无 token / 错 token → 404（fail closed）
        $this->postJson('/webhooks/email', ['from' => 'x@y.com', 'subject' => 'hi', 'text' => 'a'])->assertStatus(404);
        $this->postJson('/webhooks/email?token=wrong', ['from' => 'x@y.com', 'subject' => 'hi', 'text' => 'a'])->assertStatus(404);

        // 主题带 #TK-xxxx → 追加回复（管理员回邮件场景）
        $this->postJson('/webhooks/email?token=tok-abc', [
            'from' => 'staff@monit.test',
            'subject' => 'Re: ['.$ticket->code().'] 统计代码不生效',
            'text' => '问题已定位，请检查域名配置。',
        ])->assertStatus(204);

        $reply = TicketReply::where('ticket_id', $ticket->ticket_id)->where('via', 'email')->first();
        $this->assertNotNull($reply);
        $this->assertFalse($reply->is_staff);
        $this->assertSame(Ticket::STATUS_OPEN, $this->freshModel($ticket)->status);
        Mail::assertSent(TicketUserRepliedToAdmin::class);
    }

    public function test_email_inbound_webhook_creates_guest_ticket_without_code(): void
    {
        Mail::fake();
        Settings::set('tickets.inbound_webhook_token', 'tok-abc');
        $this->makeAdmin();

        $this->postJson('/webhooks/email?token=tok-abc', [
            'from' => 'stranger@example.com',
            'subject' => '咨询企业版报价',
            'text' => '你好，我们想了解企业版价格。',
        ])->assertStatus(204);

        $ticket = Ticket::where('email', 'stranger@example.com')->firstOrFail();
        $this->assertNull($ticket->user_id);
        $this->assertSame(1, $ticket->replies()->count());
        Mail::assertSent(TicketSubmittedToAdmin::class);
    }
}
