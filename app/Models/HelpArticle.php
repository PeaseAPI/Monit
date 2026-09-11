<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpArticle extends Model
{
    protected $table = 'help_articles';

    protected $primaryKey = 'article_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'category_id', 'title', 'url', 'content', 'description', 'is_published', 'views', 'order', 'datetime'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'views' => 'integer', 'datetime' => 'datetime'];
    }

    /**
     * @return BelongsTo<HelpCategory, $this>
     */
    public function category()
    {
        return $this->belongsTo(HelpCategory::class, 'category_id', 'category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
