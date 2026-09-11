<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $primaryKey = 'team_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'name', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'datetime' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** @return HasMany<TeamMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'team_id', 'team_id');
    }
}
