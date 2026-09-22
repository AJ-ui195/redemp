<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = [
        'item_id',
        'transaction_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'reference_number',
        'user_id',
        'location',
        'batch_number',
        'expiry_date',
        'notes',
        'status',
        'transaction_date',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_before' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'transaction_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Transaction types
     */
    const TYPE_STOCK_IN = 'stock_in';
    const TYPE_STOCK_OUT = 'stock_out';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_RETURN = 'return';
    const TYPE_DAMAGE = 'damage';
    const TYPE_EXPIRY = 'expiry';

    /**
     * Status values
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the item for this inventory transaction
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemList::class, 'item_id');
    }

    /**
     * Get the user who created this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by transaction type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Stock in transactions
     */
    public function scopeStockIn($query)
    {
        return $query->where('transaction_type', self::TYPE_STOCK_IN);
    }

    /**
     * Scope: Stock out transactions
     */
    public function scopeStockOut($query)
    {
        return $query->where('transaction_type', self::TYPE_STOCK_OUT);
    }

    /**
     * Scope: Completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Check if this is a stock increase transaction
     */
    public function isStockIn(): bool
    {
        return $this->transaction_type === self::TYPE_STOCK_IN || $this->quantity > 0;
    }

    /**
     * Check if this is a stock decrease transaction
     */
    public function isStockOut(): bool
    {
        return $this->transaction_type === self::TYPE_STOCK_OUT || $this->quantity < 0;
    }

    /**
     * Get the absolute quantity (always positive)
     */
    public function getAbsoluteQuantityAttribute(): float
    {
        return abs((float) $this->quantity);
    }
}
