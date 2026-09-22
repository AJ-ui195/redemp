<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'quantity',
        'expiry_date',
        'manufactured_date',
        'cost_per_unit',
        'supplier_id',
        'received_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'manufactured_date' => 'date',
        'received_date' => 'date',
        'cost_per_unit' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Get the product for this batch
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the supplier for this batch
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Check if the batch is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if the batch is expiring soon (within specified days)
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isFuture() && 
               $this->expiry_date->diffInDays(now()) <= $days;
    }

    /**
     * Check if the batch is depleted
     */
    public function isDepleted(): bool
    {
        return $this->quantity <= 0 || $this->status === 'depleted';
    }
}
