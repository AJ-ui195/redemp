<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemList extends Model
{
    /**
     * Flag to skip observer syncing (used when manually editing)
     */
    public $skipObserverSync = false;

    protected $table = 'item_lists';

    protected $fillable = [
        'active_status',
        'price_type',
        'item',
        'brand',
        'item_image',
        'description',
        'sales_tax_code',
        'account',
        'cogs_account',
        'asset_account',
        'accumulated_depr',
        'purchase_description',
        'quantity_on_hand',
        'unit_of_measure',
        'cost',
        'preferred_vendor',
        'tax_agency',
        'price',
        'reorder_pt_min',
        'mpn',
        'lot_number',
        'expiry_date',
        'mfg_date',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'cost' => 'decimal:4',
        'quantity_on_hand' => 'decimal:2',
        'reorder_pt_min' => 'decimal:2',
        'accumulated_depr' => 'decimal:2',
        'expiry_date' => 'date',
        'mfg_date' => 'date',
    ];

    /**
     * Accessor: Map 'item' to 'name' for compatibility
     */
    public function getNameAttribute()
    {
        return $this->item;
    }

    /**
     * Accessor: Map 'price_type' to 'category' for compatibility (backward compatibility)
     */
    public function getCategoryAttribute()
    {
        return $this->price_type;
    }

    /**
     * Accessor: Map 'quantity_on_hand' to 'stock_quantity' for compatibility
     */
    public function getStockQuantityAttribute()
    {
        return (int) $this->quantity_on_hand ?? 0;
    }

    /**
     * Accessor: Map 'reorder_pt_min' to 'min_stock_level' for compatibility
     */
    public function getMinStockLevelAttribute()
    {
        return (int) $this->reorder_pt_min ?? 10;
    }

    /**
     * Accessor: Map 'unit_of_measure' to 'unit' for compatibility
     */
    public function getUnitAttribute()
    {
        return $this->unit_of_measure ?? 'piece';
    }

    /**
     * Accessor: Map 'mpn' to 'sku' for compatibility
     */
    public function getSkuAttribute()
    {
        return $this->mpn;
    }

    /**
     * Accessor: Map 'preferred_vendor' to 'supplier' for compatibility
     */
    public function getSupplierAttribute()
    {
        return $this->preferred_vendor;
    }

    /**
     * Accessor: Map 'active_status' to 'is_active' for compatibility
     */
    public function getIsActiveAttribute()
    {
        return strtolower($this->active_status ?? '') === 'active' || $this->active_status === '1' || $this->active_status === 1;
    }

    /**
     * Scope: Filter active items
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->where('active_status', 'Active')
              ->orWhere('active_status', '1')
              ->orWhere('active_status', 1)
              ->orWhereNull('active_status');
        });
    }

    /**
     * Check if item is low stock
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->min_stock_level;
    }

    /**
     * Get the batches for this item (if using ProductBatch)
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class, 'product_id', 'id');
    }

    /**
     * Get the inventory logs for this item
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class, 'product_id', 'id');
    }

    /**
     * Get the supplier relation
     */
    public function supplierRelation(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_vendor', 'name');
    }

    /**
     * Get the barcode for this item (for product images)
     */
    public function barcode()
    {
        return $this->hasOne(Barcode::class, 'barcode_value', 'mpn');
    }

    /**
     * Accessor: Get the product image (prioritize item_lists.item_image, then barcode image)
     */
    public function getImageAttribute()
    {
        // First check if item has its own image
        if (!empty($this->attributes['item_image'])) {
            return $this->attributes['item_image'];
        }
        
        // Fall back to barcode image if available
        return $this->barcode ? $this->barcode->item_image : null;
    }

    /**
     * Sync item_image from the related barcode if available
     * @return bool Returns true if image was synced, false otherwise
     */
    public function syncImageFromBarcode()
    {
        // Skip if item already has an image
        if (!empty($this->item_image)) {
            return false;
        }

        // Check if there's a barcode with an image
        if ($this->barcode && $this->barcode->item_image) {
            $this->item_image = $this->barcode->item_image;
            $this->save();
            return true;
        }

        return false;
    }
}
