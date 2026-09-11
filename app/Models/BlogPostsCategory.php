<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostsCategory extends Model
{
    protected $table = 'blog_posts_categories';

    protected $primaryKey = 'category_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'title', 'url', 'order', 'datetime'];

    /**
     * @return array<string, string|\Stringable>
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
