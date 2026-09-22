<?php

namespace App\Support;

use App\Models\Barcode;
use App\Models\InventoryProduct;
use App\Models\ItemList;
use Illuminate\Support\Collection;

/**
 * Resolve the matching item_lists <-> inventory_products pair.
 *
 * Retail and wholesale rows often share the same item name and/or barcode.
 * Matching by name or barcode alone overwrites the wrong twin's qty/price.
 */
class ItemInventoryLinker
{
    public static function normalizePriceType($priceType): ?string
    {
        if ($priceType === null) {
            return null;
        }

        $normalized = strtolower(trim((string) $priceType));

        return $normalized === '' ? null : $normalized;
    }

    public static function priceTypesMatch($left, $right): bool
    {
        $leftType = self::normalizePriceType($left);
        $rightType = self::normalizePriceType($right);

        if ($leftType === null && $rightType === null) {
            return true;
        }

        if ($leftType === null || $rightType === null) {
            return false;
        }

        return $leftType === $rightType;
    }

    public static function quantitiesDiffer($left, $right): bool
    {
        return abs((float) ($left ?? 0) - (float) ($right ?? 0)) > 0.0001;
    }

    public static function displayedQuantity(?ItemList $itemList, ?InventoryProduct $inventoryProduct): float
    {
        if ($inventoryProduct) {
            return (float) ($inventoryProduct->quantity_on_hand ?? 0);
        }

        return (float) ($itemList->quantity_on_hand ?? 0);
    }

    public static function liveQuantityForItemList(?ItemList $itemList): float
    {
        if (!$itemList) {
            return 0.0;
        }

        return self::displayedQuantity($itemList, self::findInventoryProductForItemList($itemList));
    }

    public static function quantityForNewRow($qty): float
    {
        if ($qty === null || $qty === '') {
            return 0.0;
        }

        return max(0.0, (float) $qty);
    }

    /**
     * "1,250.00" and "₱1,250" must become 1250, not 1.
     */
    public static function parseMoney($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = trim((string) $value);
        $raw = str_replace(["\xC2\xA0", '₱', 'P', ',', ' '], '', $raw);
        if ($raw === '' || !is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }

    public static function isPlaceholderPrice($price): bool
    {
        if ($price === null || $price === '') {
            return false;
        }

        $parsed = self::parseMoney($price);
        if ($parsed === null) {
            return false;
        }

        return abs($parsed - 1.0) < 0.0001;
    }

    /**
     * Skip a 1.0000 placeholder when a twin still has the real selling price.
     *
     * @param  array<int, mixed>  $prices
     */
    public static function pickSellingPrice(array $prices): float
    {
        foreach ($prices as $price) {
            $parsed = self::parseMoney($price);
            if ($parsed === null || $parsed <= 0) {
                continue;
            }
            if (!self::isPlaceholderPrice($parsed)) {
                return $parsed;
            }
        }

        foreach ($prices as $price) {
            $parsed = self::parseMoney($price);
            if ($parsed !== null && $parsed > 0) {
                return $parsed;
            }
        }

        return 0.0;
    }

    public static function namesMatch($left, $right): bool
    {
        $leftName = self::searchableText($left);
        $rightName = self::searchableText($right);

        return $leftName !== '' && $leftName === $rightName;
    }

    /**
     * NEBULIZE 407C vs NEBULIZER 407C is the same item; Adult vs Pedia is not.
     */
    public static function namesSimilarForPrice($left, $right): bool
    {
        if (self::namesMatch($left, $right)) {
            return true;
        }

        $leftName = self::searchableText($left);
        $rightName = self::searchableText($right);
        if ($leftName === '' || $rightName === '') {
            return false;
        }

        if (self::hasConflictingNameTokens($leftName, $rightName)) {
            return false;
        }

        similar_text($leftName, $rightName, $percent);

        return $percent >= 86.0;
    }

    public static function searchableText(?string $text): string
    {
        $normalized = strtolower(trim((string) $text));
        $normalized = str_replace(["'", "’", "‘", "`", "´"], '', $normalized);
        $normalized = rtrim($normalized, " \t.,;:-");

        return preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    }

    private static function hasConflictingNameTokens(string $left, string $right): bool
    {
        $pairs = [
            ['adult', 'pedia'],
            ['adult', 'child'],
            ['adult', 'neonate'],
            ['adult', 'infant'],
            ['pedia', 'neonate'],
            ['silver', 'black'],
            ['cuffed', 'uncuffed'],
        ];

        foreach ($pairs as [$first, $second]) {
            if ((str_contains($left, $first) && str_contains($right, $second))
                || (str_contains($left, $second) && str_contains($right, $first))
            ) {
                return true;
            }
        }

        $leftWithout = str_contains($left, 'without');
        $rightWithout = str_contains($right, 'without');
        if ($leftWithout !== $rightWithout) {
            return true;
        }

        $left40 = str_contains(str_replace(' ', '', $left), '40s');
        $right40 = str_contains(str_replace(' ', '', $right), '40s');
        if ($left40 !== $right40) {
            return true;
        }

        return false;
    }

    public static function generatedBarcode(int $itemListId): string
    {
        return 'ITEM-' . str_pad((string) $itemListId, 8, '0', STR_PAD_LEFT);
    }

    public static function applyPriceType($query, $priceType, string $column = 'price_type')
    {
        if (!in_array($column, ['price_type'], true)) {
            throw new \InvalidArgumentException('Invalid price type column');
        }

        $normalized = self::normalizePriceType($priceType);

        if ($normalized === null) {
            return $query;
        }

        return $query->whereRaw("LOWER(TRIM({$column})) = ?", [$normalized]);
    }

    public static function findInventoryProductByBarcodeAndPriceType(?string $barcode, $priceType = null): ?InventoryProduct
    {
        $barcode = trim((string) $barcode);
        if ($barcode === '') {
            return null;
        }

        return self::applyPriceType(
            InventoryProduct::where('barcode_value', $barcode),
            $priceType
        )->first();
    }

    public static function imagePathIsEmpty($path): bool
    {
        return trim((string) $path) === '';
    }

    public static function findMatchingInventoryProductForBarcode(Collection $inventoryProducts, Barcode $barcode): ?InventoryProduct
    {
        $barcodeValue = trim((string) $barcode->barcode_value);
        $priceType = $barcode->price_type;

        if ($barcodeValue !== '') {
            $byBarcode = $inventoryProducts->first(function ($row) use ($barcodeValue, $priceType) {
                return trim((string) ($row->barcode_value ?? '')) === $barcodeValue
                    && self::priceTypesMatch($row->price_type, $priceType);
            });
            if ($byBarcode) {
                return $byBarcode;
            }
        }

        $name = trim((string) $barcode->item_name);
        if ($name === '') {
            return null;
        }

        $needle = self::searchableText($name);

        return $inventoryProducts->first(function ($row) use ($needle, $priceType) {
            return self::searchableText((string) ($row->item_name ?? '')) === $needle
                && self::priceTypesMatch($row->price_type, $priceType);
        });
    }

    public static function findMatchingItemListForBarcode(Collection $itemLists, Barcode $barcode): ?ItemList
    {
        $barcodeValue = trim((string) $barcode->barcode_value);
        $priceType = $barcode->price_type;

        if ($barcodeValue !== '') {
            $byBarcode = $itemLists->first(function ($row) use ($barcodeValue, $priceType) {
                return trim((string) ($row->mpn ?? '')) === $barcodeValue
                    && self::priceTypesMatch($row->price_type, $priceType);
            });
            if ($byBarcode) {
                return $byBarcode;
            }
        }

        $name = trim((string) $barcode->item_name);
        if ($name === '') {
            return null;
        }

        $needle = self::searchableText($name);

        return $itemLists->first(function ($row) use ($needle, $priceType) {
            return self::searchableText((string) ($row->item ?? '')) === $needle
                && self::priceTypesMatch($row->price_type, $priceType);
        });
    }

    public static function ensureInventoryProductFromBarcode(Barcode $barcode): InventoryProduct
    {
        $existing = self::findInventoryProductByBarcodeAndPriceType(
            $barcode->barcode_value,
            $barcode->price_type
        );

        if ($existing) {
            return $existing;
        }

        return InventoryProduct::create([
            'barcode_value' => $barcode->barcode_value,
            'item_name' => $barcode->item_name,
            'description' => $barcode->description,
            'price' => $barcode->price,
            'original_price' => $barcode->original_price,
            'price_type' => $barcode->price_type,
            'unit' => $barcode->unit ?? 'pcs',
            'quantity_on_hand' => self::quantityForNewRow($barcode->quantity_on_hand),
            'expiration_date' => $barcode->expiration_date,
            'mfg_date' => $barcode->mfg_date,
            'active_status' => $barcode->active_status ?? 'Active',
            'item_image' => $barcode->item_image,
            'brand' => $barcode->brand,
            'lot_number' => $barcode->lot_number,
        ]);
    }

    public static function findInventoryProductForItemList(?ItemList $itemList): ?InventoryProduct
    {
        if (!$itemList) {
            return null;
        }

        $priceType = $itemList->price_type;

        if (!empty($itemList->id)) {
            $byGenerated = InventoryProduct::where('barcode_value', self::generatedBarcode((int) $itemList->id))->first();
            if ($byGenerated && self::priceTypesMatch($priceType, $byGenerated->price_type)) {
                return $byGenerated;
            }
        }

        if (!empty($itemList->mpn)) {
            $byBarcodeAndType = self::applyPriceType(
                InventoryProduct::where('barcode_value', $itemList->mpn),
                $priceType
            )->first();

            if ($byBarcodeAndType) {
                return $byBarcodeAndType;
            }

            $barcodeMatches = InventoryProduct::where('barcode_value', $itemList->mpn)->get();
            if ($barcodeMatches->count() === 1 && self::priceTypesMatch($priceType, $barcodeMatches->first()->price_type)) {
                return $barcodeMatches->first();
            }

            // Barcode belongs to a different price type — do not overwrite it.
            if ($barcodeMatches->isNotEmpty() && self::normalizePriceType($priceType) !== null) {
                return self::findInventoryProductByNameAndPriceType($itemList->item, $priceType);
            }
        }

        return self::findInventoryProductByNameAndPriceType($itemList->item, $priceType);
    }

    public static function findItemListForInventoryProduct(?InventoryProduct $inventoryProduct, ?string $itemName = null): ?ItemList
    {
        if (!$inventoryProduct) {
            return null;
        }

        $name = $itemName ?: $inventoryProduct->item_name;
        $priceType = $inventoryProduct->price_type;

        if (!empty($inventoryProduct->barcode_value)) {
            if (preg_match('/^ITEM-(\d+)$/', $inventoryProduct->barcode_value, $matches)) {
                $byGenerated = ItemList::find((int) $matches[1]);
                if ($byGenerated && self::priceTypesMatch($priceType, $byGenerated->price_type)) {
                    return $byGenerated;
                }
            }

            $byBarcodeAndType = self::applyPriceType(
                ItemList::where('mpn', $inventoryProduct->barcode_value),
                $priceType
            )->first();

            if ($byBarcodeAndType) {
                return $byBarcodeAndType;
            }

            $barcodeMatches = ItemList::where('mpn', $inventoryProduct->barcode_value)->get();
            if ($barcodeMatches->count() === 1 && self::priceTypesMatch($priceType, $barcodeMatches->first()->price_type)) {
                return $barcodeMatches->first();
            }

            if ($barcodeMatches->isNotEmpty() && self::normalizePriceType($priceType) !== null) {
                return self::findItemListByNameAndPriceType($name, $priceType);
            }
        }

        return self::findItemListByNameAndPriceType($name, $priceType);
    }

    public static function findItemListByNameAndPriceType(?string $name, $priceType = null): ?ItemList
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $query = ItemList::where('item', $name);
        $normalized = self::normalizePriceType($priceType);

        if ($normalized !== null) {
            $typed = self::applyPriceType($query->clone(), $normalized)->first();
            if ($typed) {
                return $typed;
            }

            return null;
        }

        $matches = $query->get();
        if ($matches->count() === 1) {
            return $matches->first();
        }

        return null;
    }

    public static function findInventoryProductByNameAndPriceType(?string $name, $priceType = null): ?InventoryProduct
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $query = InventoryProduct::where('item_name', $name);
        $normalized = self::normalizePriceType($priceType);

        if ($normalized !== null) {
            $typed = self::applyPriceType($query->clone(), $normalized)->first();
            if ($typed) {
                return $typed;
            }

            return null;
        }

        $matches = $query->get();
        if ($matches->count() === 1) {
            return $matches->first();
        }

        return null;
    }

    public static function findBarcodeByNameAndPriceType(?string $name, $priceType = null): ?Barcode
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $query = Barcode::where('item_name', $name);
        $normalized = self::normalizePriceType($priceType);

        if ($normalized !== null) {
            $typed = self::applyPriceType($query->clone(), $normalized)->first();
            if ($typed) {
                return $typed;
            }

            return null;
        }

        $matches = $query->get();
        if ($matches->count() === 1) {
            return $matches->first();
        }

        return null;
    }

    public static function findBarcodeByValueAndPriceType(?string $barcodeValue, $priceType = null): ?Barcode
    {
        $barcodeValue = trim((string) $barcodeValue);
        if ($barcodeValue === '' || strcasecmp($barcodeValue, 'N/A') === 0) {
            return null;
        }

        $query = Barcode::where('barcode_value', $barcodeValue);
        $normalized = self::normalizePriceType($priceType);

        if ($normalized !== null) {
            $typed = self::applyPriceType($query->clone(), $normalized)->first();
            if ($typed) {
                return $typed;
            }
        }

        $matches = $query->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public static function findMatchingItemListFromCollection(Collection $itemLists, InventoryProduct $inventoryProduct): ?ItemList
    {
        $priceType = self::normalizePriceType($inventoryProduct->price_type);
        $barcode = $inventoryProduct->barcode_value;
        $name = $inventoryProduct->item_name;

        if (!empty($barcode) && preg_match('/^ITEM-(\d+)$/', (string) $barcode, $matches)) {
            $byGenerated = $itemLists->first(function ($item) use ($matches, $priceType) {
                return (int) $item->id === (int) $matches[1]
                    && self::priceTypesMatch($priceType, $item->price_type);
            });
            if ($byGenerated) {
                return $byGenerated;
            }
        }

        if (!empty($barcode)) {
            $byBarcode = $itemLists->filter(function ($item) use ($barcode) {
                return !empty($item->mpn) && $item->mpn === $barcode;
            });

            if ($priceType) {
                $typed = $byBarcode->first(function ($item) use ($priceType) {
                    return self::normalizePriceType($item->price_type) === $priceType;
                });
                if ($typed) {
                    return $typed;
                }
            } elseif ($byBarcode->count() === 1) {
                return $byBarcode->first();
            }
        }

        if (!empty($name)) {
            $byName = $itemLists->filter(function ($item) use ($name) {
                return self::namesMatch($item->item, $name);
            });

            if ($priceType) {
                $typed = $byName->first(function ($item) use ($priceType) {
                    return self::normalizePriceType($item->price_type) === $priceType;
                });
                if ($typed) {
                    return $typed;
                }

                return null;
            }

            if ($byName->count() === 1) {
                return $byName->first();
            }
        }

        return null;
    }

    public static function findMatchingInventoryProductFromCollection(Collection $inventoryProducts, ItemList $itemList): ?InventoryProduct
    {
        $priceType = self::normalizePriceType($itemList->price_type);
        $barcode = $itemList->mpn;
        $name = $itemList->item;
        $generated = !empty($itemList->id) ? self::generatedBarcode((int) $itemList->id) : null;

        if (!empty($generated)) {
            $byGenerated = $inventoryProducts->first(function ($product) use ($generated) {
                return ($product->barcode_value ?? null) === $generated;
            });
            if ($byGenerated && self::priceTypesMatch($priceType, $byGenerated->price_type)) {
                return $byGenerated;
            }
        }

        if (!empty($barcode)) {
            $byBarcode = $inventoryProducts->filter(function ($product) use ($barcode) {
                return !empty($product->barcode_value) && $product->barcode_value === $barcode;
            });

            if ($priceType) {
                $typed = $byBarcode->first(function ($product) use ($priceType) {
                    return self::normalizePriceType($product->price_type) === $priceType;
                });
                if ($typed) {
                    return $typed;
                }
            } elseif ($byBarcode->count() === 1) {
                $only = $byBarcode->first();
                if ($only && self::priceTypesMatch($priceType, $only->price_type)) {
                    return $only;
                }
            }
        }

        if (!empty($name)) {
            $byName = $inventoryProducts->filter(function ($product) use ($name) {
                return self::namesMatch($product->item_name, $name);
            });

            if ($priceType) {
                $typed = $byName->first(function ($product) use ($priceType) {
                    return self::normalizePriceType($product->price_type) === $priceType;
                });
                if ($typed) {
                    return $typed;
                }

                return null;
            }

            if ($byName->count() === 1) {
                return $byName->first();
            }
        }

        return null;
    }

    /**
     * Pick a barcode that will not attach a new inventory_products row
     * onto a different price-type twin that already owns the MPN.
     */
    public static function uniqueBarcodeForNewInventoryProduct(ItemList $itemList): string
    {
        if (!empty($itemList->mpn)) {
            $taken = InventoryProduct::where('barcode_value', $itemList->mpn)->first();
            if (!$taken || self::priceTypesMatch($itemList->price_type, $taken->price_type)) {
                return $itemList->mpn;
            }
        }

        if (!empty($itemList->id)) {
            $generated = self::generatedBarcode((int) $itemList->id);
            if (!InventoryProduct::where('barcode_value', $generated)->exists()) {
                return $generated;
            }
        }

        do {
            $barcodeValue = 'ITEM-' . str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        } while (InventoryProduct::where('barcode_value', $barcodeValue)->exists());

        return $barcodeValue;
    }
}
