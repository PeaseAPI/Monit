<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardView extends Model
{
    protected $primaryKey = 'dashboard_view_id';

    protected $fillable = [
        'website_id', 'user_id', 'name', 'settings', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'datetime' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}