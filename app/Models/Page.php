<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'pages';

    protected $primaryKey = 'page_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'title', 'url', 'content', 'description',
        'image', 'type', 'position', 'order', 'is_published', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'datetime' => 'datetime',
        ];
    }
}

class PageCategory extends Model
{
    protected $table = 'pages_categories';

    protected $primaryKey = 'page_category_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'title', 'url', 'order', 'datetime',
    ];
}
