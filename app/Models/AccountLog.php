<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountLog extends Model
{
    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'type', 'ip', 'device_type', 'os_name', 'browser_name',
        'continent_code', 'country_code', 'city_name', 'datetime',
    ];

        protected function casts(): array
    {
        return [
            'datetime' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
