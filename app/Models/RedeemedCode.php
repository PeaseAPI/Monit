<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedeemedCode extends Model
{
    protected $table = 'redeemed_codes';

    protected $primaryKey = 'redeemed_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'code_id', 'datetime'];

    protected function casts(): array
    {
        return ['datetime' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function code()
    {
        return $this->belongsTo(Code::class, 'code_id', 'code_id');
    }
}