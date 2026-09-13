<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 社交账号绑定（账户页「社交登录」标签）
 * 一个 Monit 账号可绑定多个第三方身份；同一第三方身份全局唯一（provider + provider_user_id）
 */
class SocialAccount extends Model
{
    protected $table = 'social_accounts';

    protected $primaryKey = 'social_account_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'provider', 'provider_user_id', 'nickname', 'email', 'avatar', 'datetime'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['datetime' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
