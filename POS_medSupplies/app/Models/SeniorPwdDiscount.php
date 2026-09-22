<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeniorPwdDiscount extends Model
{
    protected $fillable = [
        'sale_id',
        'discount_type',
        'customer_name',
        'id_number',
        'id_type',
        'issuing_lgu',
        'id_image',
        'captured_by',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    /**
     * Get the sale associated with this discount
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the user who captured the ID
     */
    public function capturedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }
}
