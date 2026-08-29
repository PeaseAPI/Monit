<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateWithdrawal extends Model
{
    protected $table = 'affiliates_withdrawals';

    protected $primaryKey = 'affiliate_withdrawal_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'amount', 'currency', 'note', 'status', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'datetime' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}