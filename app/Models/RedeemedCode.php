<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedeemedCode extends Model
{
    protected $table = 'redeemed_codes';

    protected $primaryKey = 'redeemed_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'code_id', 'datetime'];

    /**
     * @return array<string, mixed>
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

    /**
     * @return BelongsTo<Code, $this>
     */
    public function code()
    {
        return $this->belongsTo(Code::class, 'code_id', 'code_id');
    }
}
