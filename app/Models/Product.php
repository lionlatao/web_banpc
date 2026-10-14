<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = ['specs' => 'array', 'active' => 'boolean', 'price' => 'integer', 'stock' => 'integer'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
