<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpCategory extends Model
{
    protected $table = 'help_categories';

    protected $primaryKey = 'category_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'title', 'url', 'icon', 'order', 'datetime'];

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
     * @return HasMany<HelpArticle, $this>
     */
    public function articles()
    {
        return $this->hasMany(HelpArticle::class, 'category_id', 'category_id');
    }
}
