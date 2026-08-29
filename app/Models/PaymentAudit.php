<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAudit extends Model
{
    protected $table = 'payments_audit';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'payment_id', 'type', 'ip', 'datetime',
    ];

    protected function casts(): array
    {
        return ['datetime' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }
}