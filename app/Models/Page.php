<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * -- pages 表为可选模块（可能缺失，读取处有 try-catch 防御）：
 *
 * @property Carbon|null $updated_at
 */
class Page extends Model
{
    protected $table = 'pages';

    protected $primaryKey = 'page_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'title', 'url', 'content', 'description',
        'image', 'type', 'position', 'order', 'is_published', 'datetime',
    ];

    /**
     * @return array<string, string|\Stringable>
     */
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
