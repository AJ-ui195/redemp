<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleStatus extends Model
{
    protected $fillable = [
        'name',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
