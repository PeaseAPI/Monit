<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $conversions 视图层临时计数（stats.goals 由 withCount 写入，非数据列）
 */
class WebsiteGoal extends Model
{
    protected $table = 'websites_goals';

    protected $primaryKey = 'goal_id';

    protected $fillable = [
        'website_id', 'key', 'type', 'path', 'scroll_percentage', 'name', 'is_enabled',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'scroll_percentage' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    /**
     * @return HasMany<GoalConversion, $this>
     */
    public function conversions()
    {
        return $this->hasMany(GoalConversion::class, 'goal_id', 'goal_id');
    }
}
