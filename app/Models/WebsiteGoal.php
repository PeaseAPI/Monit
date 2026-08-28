<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteGoal extends Model
{
    protected $table = 'websites_goals';

    protected $primaryKey = 'goal_id';

    protected $fillable = [
        'website_id', 'key', 'type', 'path', 'scroll_percentage', 'name', 'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'scroll_percentage' => 'integer',
        ];
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    public function conversions()
    {
        return $this->hasMany(GoalConversion::class, 'goal_id', 'goal_id');
    }
}
