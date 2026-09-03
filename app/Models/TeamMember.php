<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
