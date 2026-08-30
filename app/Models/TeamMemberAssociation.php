<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 团队成员-网站关联模型（规格书 §6.2.4：teams-associations-ajax）
 */
class TeamMemberAssociation extends Model
{
    protected $table = 'team_member_associations';

    public $timestamps = false;

    protected $fillable = [
        'team_member_id',
        'website_id',
        'access',
    ];

    protected function casts(): array
    {
        return [
            'access' => 'array',
        ];
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    public function member()
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id', 'team_member_id');
    }
}
