<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpCategory extends Model
{
    protected $table = 'help_categories';

    protected $primaryKey = 'category_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'title', 'url', 'icon', 'order', 'datetime'];

    protected function casts(): array
    {
        return ['datetime' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function articles()
    {
        return $this->hasMany(HelpArticle::class, 'category_id', 'category_id');
    }
}
