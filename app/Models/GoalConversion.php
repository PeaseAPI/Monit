<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalConversion extends Model
{
    protected $table = 'goals_conversions';

    protected $primaryKey = 'conversion_id';

    protected $fillable = [
        'goal_id', 'event_id', 'session_id', 'visitor_id', 'website_id', 'expiration_date',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<WebsiteGoal, $this>
     */
    public function goal()
    {
        return $this->belongsTo(WebsiteGoal::class, 'goal_id', 'goal_id');
    }

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
