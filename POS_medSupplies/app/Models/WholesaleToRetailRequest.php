<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WholesaleToRetailRequest extends Model
{
    protected $fillable = [
        'retail_item_id',
        'wholesale_item_id',
        'requested_by',
        'status',
        'approved_by',
        'request_notes',
        'admin_notes',
        'quantity_to_convert',
        'requested_at',
        'processed_at',
    ];

    protected $casts = [
        'quantity_to_convert' => 'decimal:2',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the retail item that is out of stock
     */
    public function retailItem(): BelongsTo
    {
        return $this->belongsTo(ItemList::class, 'retail_item_id');
    }

    /**
     * Get the wholesale item to convert
     */
    public function wholesaleItem(): BelongsTo
    {
        return $this->belongsTo(ItemList::class, 'wholesale_item_id');
    }

    /**
     * Get the user who requested the conversion
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the admin who approved/rejected
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if request is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
