<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalConversion extends Model
{
    protected $table = 'goals_conversions';

    protected $primaryKey = 'conversion_id';

    protected $fillable = [
        'goal_id', 'event_id', 'session_id', 'visitor_id', 'website_id', 'expiration_date',
    ];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
        ];
    }

    public function goal()
    {
        return $this->belongsTo(WebsiteGoal::class, 'goal_id', 'goal_id');
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
