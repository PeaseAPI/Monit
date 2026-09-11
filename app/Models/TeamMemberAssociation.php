<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'datetime',
    ];

    protected function casts(): array
    {
        return [
            'access' => 'array',
        ];
    }

    /** @return BelongsTo<Website, $this> */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    /** @return BelongsTo<TeamMember, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id', 'team_member_id');
    }
}
