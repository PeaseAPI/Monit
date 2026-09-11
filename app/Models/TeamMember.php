<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array<int, int> $websites_ids
 */
class TeamMember extends Model
{
    protected $primaryKey = 'team_member_id';

    public $timestamps = false;

    protected $fillable = [
        'team_id', 'user_email', 'user_id', 'is_owned',
        'websites_ids', 'access', 'status', 'last_activity', 'datetime',
    ];

    protected static function booted(): void
    {
        // 级联清理关联（团队/成员删除路径共 4 处：TeamController::remove/destroy、
        // AdminTeams::destroyMember/destroy），避免 team_member_associations 孤儿累积
        static::deleting(function (self $member): void {
            TeamMemberAssociation::where('team_member_id', $member->team_member_id)->delete();
        });
    }

    /**
     * @return array<string, string|\Stringable>
     */
    protected function casts(): array
    {
        return [
            'is_owned' => 'boolean',
            'websites_ids' => 'array',
            'access' => 'array',
            'last_activity' => 'datetime',
            'datetime' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    /** @return HasMany<TeamMemberAssociation, $this> */
    /**
     * @return HasMany<TeamMemberAssociation, $this>
     */
    public function associations(): HasMany
    {
        return $this->hasMany(TeamMemberAssociation::class, 'team_member_id', 'team_member_id');
    }

    /** @return BelongsTo<User, $this> */
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
