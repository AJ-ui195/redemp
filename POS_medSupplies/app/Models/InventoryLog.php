<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'action',
        'quantity_before',
        'quantity_after',
        'quantity_changed',
        'description',
        'metadata',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'quantity_changed' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the product for this log entry
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who performed this action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a log entry for inventory action
     */
    public static function logAction(
        int $productId,
        int $userId,
        string $action,
        ?int $quantityBefore,
        ?int $quantityAfter,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        $quantityChanged = null;
        if ($quantityBefore !== null && $quantityAfter !== null) {
            $quantityChanged = $quantityAfter - $quantityBefore;
        }

        return self::create([
            'product_id' => $productId,
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
