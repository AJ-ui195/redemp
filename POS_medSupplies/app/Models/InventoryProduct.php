<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $expiration_date
 * @property Carbon|null $mfg_date
 */
class InventoryProduct extends Model
{
    protected $table = 'inventory_products';

    protected $fillable = [
        'active_status',
        'barcode_value',
        'item_name',
        'item_image',
        'brand',
        'description',
        'price',
        'price_type',
        'unit',
        'quantity_on_hand',
        'expiration_date',
        'mfg_date',
        'original_price',
        'lot_number',
    ];

    /**
     * Normalize unit value to standard format
     */
    private function normalizeUnit($unit)
    {
        if (empty($unit)) {
            return 'pcs';
        }

        $unit = strtolower(trim($unit));
        
        // Map common unit variations
        $unitMap = [
            'piece (pc)' => 'pcs',
            'pieces' => 'pcs',
            'pc' => 'pcs',
            'pair (pair)' => 'pair',
            'pairs' => 'pair',
            'bottle (btl)' => 'bottle',
            'bottles' => 'bottle',
            'pack (pck)' => 'pack',
            'packs' => 'pack',
            'box (box)' => 'box',
            'boxes' => 'box',
            'liter (ltr)' => 'liter',
            'liters' => 'liter',
            'gallon (gal)' => 'gallon',
            'gallons' => 'gallon',
        ];

        return $unitMap[$unit] ?? $unit;
    }

    protected $casts = [
        'price' => 'decimal:4',
        'original_price' => 'decimal:4',
        'quantity_on_hand' => 'decimal:4',
        'expiration_date' => 'date',
        'mfg_date' => 'date',
    ];

    /**
     * Scope: Filter active products
     */
    public function scopeActive($query)
    {
        return $query->where('active_status', 'Active');
    }

    /**
     * Scope: Filter inactive products
     */
    public function scopeInactive($query)
    {
        return $query->where('active_status', 'Inactive');
    }

    /**
     * Scope: Filter products expiring soon (within 30 days)
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('expiration_date')
            ->where('expiration_date', '>', now())
            ->where('expiration_date', '<=', now()->addDays($days));
    }

    /**
     * Scope: Filter expired products
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now());
    }

    /**
     * Scope: Filter low stock products (quantity <= 10)
     */
    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->where('quantity_on_hand', '<=', $threshold)
            ->where('quantity_on_hand', '>', 0);
    }

    /**
     * Scope: Filter out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_on_hand', '<=', 0);
    }

    /**
     * Check if product is active
     */
    public function isActive(): bool
    {
        return $this->active_status === 'Active';
    }

    /**
     * Check if product is expired
     */
    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }

    /**
     * Check if product is expiring soon
     */
    public function isExpiringSoon($days = 30): bool
    {
        if (!$this->expiration_date) {
            return false;
        }
        
        return $this->expiration_date->isFuture() 
            && $this->expiration_date->diffInDays(now()) <= $days;
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock($threshold = 10): bool
    {
        return $this->quantity_on_hand > 0 && $this->quantity_on_hand <= $threshold;
    }

    /**
     * Check if product is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity_on_hand <= 0;
    }

    /**
     * Get the logs for this inventory product
     */
    public function logs(): HasMany
    {
        return $this->hasMany(InventoryProductLog::class, 'inventory_product_id');
    }

    /**
     * Get the previous quantity from logs
     */
    public function getPreviousQuantityAttribute(): ?float
    {
        $latestLog = $this->logs()
            ->whereNotNull('quantity_before')
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $latestLog ? (float) $latestLog->quantity_before : null;
    }

    /**
     * Get quantity change from previous to current
     */
    public function getQuantityChangeAttribute(): ?float
    {
        $previous = $this->previous_quantity;
        if ($previous === null) {
            return null;
        }
        
        return (float) $this->quantity_on_hand - $previous;
    }

    /**
     * Flag to skip automatic logging (used when manually logging)
     */
    public $skipLogging = false;

    /**
     * Boot method to log quantity changes
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($product) {
            // Skip logging if manually logged or if skipLogging flag is set
            if ($product->skipLogging) {
                return;
            }
            
            if ($product->isDirty('quantity_on_hand')) {
                // Get the actual current quantity from database before update
                $original = $product->getOriginal('quantity_on_hand');
                $new = $product->quantity_on_hand;
                
                // Ensure we have valid numeric values
                $original = is_numeric($original) ? (float) $original : 0;
                $new = is_numeric($new) ? (float) $new : 0;
                
                InventoryProductLog::logQuantityChange(
                    $product->id,
                    auth()->id(),
                    'update',
                    $original,
                    $new,
                    'Quantity updated',
                    ['updated_at' => now()->toDateTimeString()]
                );
            }
        });
        
        static::updated(function ($product) {
            // After update, verify the quantity_on_hand matches what was logged
            if ($product->wasChanged('quantity_on_hand') && !$product->skipLogging) {
                // Refresh to get actual database value
                $product->refresh();
                $actualQuantity = (float) ($product->quantity_on_hand ?? 0);
                
                // Get the latest log entry
                $latestLog = $product->logs()->latest()->first();
                if ($latestLog && abs($latestLog->quantity_after - $actualQuantity) > 0.0001) {
                    // Update the log if there's a discrepancy
                    $latestLog->update(['quantity_after' => $actualQuantity]);
                    \Log::warning('Quantity mismatch corrected in log', [
                        'inventory_product_id' => $product->id,
                        'logged_quantity' => $latestLog->quantity_after,
                        'actual_quantity' => $actualQuantity
                    ]);
                }
            }
        });
    }
}
