<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageCategory extends Model
{
    protected $table = 'pages_categories';

    protected $primaryKey = 'page_category_id';

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
