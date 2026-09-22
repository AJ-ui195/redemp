<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestStatus extends Model
{
    protected $fillable = [
        'name',
    ];

    public function voidRequests(): HasMany
    {
        return $this->hasMany(VoidRequest::class);
    }

    public function damageRequests(): HasMany
    {
        return $this->hasMany(DamageRequest::class);
    }

    public function freeSampleRequests(): HasMany
    {
        return $this->hasMany(FreeSampleRequest::class);
    }
}
