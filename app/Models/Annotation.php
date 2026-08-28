<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Annotation extends Model
{
    protected $primaryKey = 'annotation_id';

    protected $fillable = ['website_id', 'user_id', 'name', 'date'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
