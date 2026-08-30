<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPostsCategory extends Model
{
    protected $table = 'blog_posts_categories';

    protected $primaryKey = 'category_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'title', 'url', 'order', 'datetime'];

    protected function casts(): array
    {
        return ['datetime' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
