<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'category_id',
        'unit_id',
        'description',
        'price',
        'selling_price',
        'price_type',
        'cost_price',
        'quantity',
        'stock_quantity',
        'min_stock_level',
        'unit',
        'sku',
        'barcode',
        'image',
        'brand',
        'supplier',
        'lot_number',
        'mfg_date',
        'expiration_date',
        'expiry_date',
        'requires_prescription',
        'is_active',
    ];

    public const PRICE_TYPE_RETAIL = 'retail';

    public const PRICE_TYPE_WHOLESALE = 'wholesale';

    protected $casts = [
        'price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'quantity' => 'integer',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'requires_prescription' => 'boolean',
        'is_active' => 'boolean',
        'mfg_date' => 'date',
        'expiration_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function isLowStock(): bool
    {
        $qty = $this->quantity ?? $this->stock_quantity;

        return $qty <= ($this->min_stock_level ?? 0);
    }

    public function isExpired(): bool
    {
        $date = $this->expiration_date ?? $this->expiry_date;

        return $date && $date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        $date = $this->expiration_date ?? $this->expiry_date;

        return $date && $date->isFuture() && $date->diffInDays(now()) <= $days;
    }

    public function scannedProducts(): HasMany
    {
        return $this->hasMany(ScannedProduct::class);
    }

    /**
     * Get the batches for this product
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    /**
     * Get the inventory logs for this product
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Get the supplier for this product
     */
    public function supplierRelation(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
