<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PcBuild extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(BuildItem::class);
    }
}
