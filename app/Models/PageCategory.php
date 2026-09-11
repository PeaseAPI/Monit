<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageCategory extends Model
{
    protected $table = 'pages_categories';

    protected $primaryKey = 'page_category_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'title', 'url', 'order', 'datetime'];

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
}
