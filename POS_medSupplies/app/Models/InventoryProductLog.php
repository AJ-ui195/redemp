<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryProductLog extends Model
{
    protected $table = 'inventory_product_logs';

    protected $fillable = [
        'inventory_product_id',
        'user_id',
        'action',
        'quantity_before',
        'quantity_after',
        'quantity_changed',
        'description',
        'metadata',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:4',
        'quantity_after' => 'decimal:4',
        'quantity_changed' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Get the inventory product for this log entry
     */
    public function inventoryProduct(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class, 'inventory_product_id');
    }

    /**
     * Get the user who performed this action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a log entry for inventory product quantity change
     */
    public static function logQuantityChange(
        int $inventoryProductId,
        ?int $userId,
        string $action,
        ?float $quantityBefore,
        ?float $quantityAfter,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        $quantityChanged = null;
        if ($quantityBefore !== null && $quantityAfter !== null) {
            $quantityChanged = $quantityAfter - $quantityBefore;
        }

        return self::create([
            'inventory_product_id' => $inventoryProductId,
            'user_id' => $userId,
            'action' => $action,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'quantity_changed' => $quantityChanged,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
