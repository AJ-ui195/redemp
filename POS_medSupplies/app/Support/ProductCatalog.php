<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Product catalog for cashier and inventory — sourced from the products table.
 */
class ProductCatalog
{
    /**
     * @return Collection<int, object>
     */
    public static function all(): Collection
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }

        $query = Product::query()->orderBy('name');

        if (Schema::hasColumn('products', 'is_active')) {
            $query->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            });
        }

        if (Schema::hasColumn('products', 'category_id')) {
            $query->with(['category', 'unit']);
        }

        return $query->get()
            ->map(fn (Product $product) => self::fromProduct($product))
            ->sortBy(fn ($row) => mb_strtolower((string) ($row->item_name ?? '')))
            ->values();
    }

    /**
     * Full catalog including archived/inactive products (inventory admin views).
     *
     * @return Collection<int, object>
     */
    public static function allIncludingInactive(): Collection
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }

        $query = Product::query()->orderBy('name');

        if (Schema::hasColumn('products', 'category_id')) {
            $query->with(['category', 'unit']);
        }

        return $query->get()
            ->map(fn (Product $product) => self::fromProduct($product))
            ->sortBy(fn ($row) => mb_strtolower((string) ($row->item_name ?? '')))
            ->values();
    }

    public static function fromProduct(Product $product): object
    {
        $unitName = 'pcs';
        if ($product->relationLoaded('unit') && $product->unit) {
            $unitName = $product->unit->abbreviation ?: $product->unit->name ?: 'pcs';
        }

        $activeStatus = ($product->is_active ?? true) ? 'Active' : 'Inactive';

        return (object) [
            'source' => 'products',
            'id' => $product->id,
            'item_list_id' => $product->id,
            'inventory_product_id' => $product->id,
            'barcode_id' => null,
            'item_name' => $product->name,
            'item_image' => $product->image,
            'description' => null,
            'brand' => $product->brand,
            'barcode' => $product->barcode ?: $product->sku,
            'quantity_on_hand' => (float) ($product->quantity ?? 0),
            'unit' => $unitName,
            'lot_number' => $product->lot_number,
            'price' => (float) ($product->selling_price ?? $product->price ?? 0),
            'cost_price' => (float) ($product->cost_price ?? 0),
            'price_type' => $product->price_type ?? Product::PRICE_TYPE_RETAIL,
            'expiration_date' => $product->expiration_date,
            'mfg_date' => $product->mfg_date,
            'active_status' => $activeStatus,
            'sku' => $product->sku,
            'category_id' => $product->category_id,
            'itemList' => null,
            'inventoryProduct' => null,
            'product' => $product,
            'cashier_key' => 'p-' . $product->id,
        ];
    }

    public static function matchesSearch(object $row, string $term): bool
    {
        $needle = ItemInventoryLinker::searchableText($term);
        if ($needle === '') {
            return true;
        }

        foreach ([
            $row->item_name ?? '',
            $row->brand ?? '',
            $row->description ?? '',
            $row->lot_number ?? '',
            $row->barcode ?? '',
            $row->sku ?? '',
        ] as $haystack) {
            if (str_contains(ItemInventoryLinker::searchableText((string) $haystack), $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Duplicate only when name + brand + price_type all match (3-part product identity).
     * Same name / different brand, same brand / different name, or different price_type → not a duplicate.
     *
     * @param  list<string>|null  $sources
     */
    public static function duplicateExists(
        ?string $barcode,
        $priceType,
        ?string $name = null,
        ?string $exceptSource = null,
        $exceptId = null,
        ?array $sources = null,
        ?string $brand = null
    ): bool {
        return self::findByIdentity($name, $brand, $priceType, $exceptId) !== null;
    }

    /**
     * Find an existing product by catalog identity: name + brand + price_type.
     */
    public static function findByIdentity(
        ?string $name,
        ?string $brand,
        $priceType,
        $exceptId = null
    ): ?Product {
        if (! Schema::hasTable('products')) {
            return null;
        }

        $name = ItemInventoryLinker::searchableText($name);
        if ($name === '') {
            return null;
        }

        $brand = self::normalizeBrand($brand);
        $normalizedType = ItemInventoryLinker::normalizePriceType($priceType);

        return Product::query()
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->get()
            ->first(function (Product $row) use ($name, $brand, $normalizedType) {
                if (ItemInventoryLinker::searchableText((string) $row->name) !== $name) {
                    return false;
                }

                if (self::normalizeBrand($row->brand) !== $brand) {
                    return false;
                }

                return ItemInventoryLinker::priceTypesMatch($row->price_type ?? null, $normalizedType);
            });
    }

    public static function normalizeBrand(?string $brand): string
    {
        return ItemInventoryLinker::searchableText($brand);
    }
}
