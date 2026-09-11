<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPost extends Model
{
    protected $table = 'blog_posts';

    protected $primaryKey = 'post_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'title', 'url', 'category_id', 'content',
        'description', 'image', 'type', 'is_published', 'datetime',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'datetime' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BlogPostCategory, $this>
     */
    public function category()
    {
        return $this->belongsTo(BlogPostCategory::class, 'category_id', 'category_id');
    }
}

class BlogPostCategory extends Model
{
    protected $table = 'blog_posts_categories';

    protected $primaryKey = 'category_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'title', 'url', 'order', 'datetime',
    ];
}
