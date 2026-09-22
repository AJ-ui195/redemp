<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Temporary barcode drafts created by "Generate Barcode".
 * Committed to products only when BarcodeScanner taps Add.
 */
class PendingBarcodeDraft
{
    private const CACHE_PREFIX = 'pending_barcode_draft:';

    private const TTL_SECONDS = 86400; // 24 hours

    public static function cacheKey(string $barcode): string
    {
        return self::CACHE_PREFIX.strtolower(trim($barcode));
    }

    public static function put(string $barcode, array $payload): void
    {
        $barcode = trim($barcode);
        // Replace any previous draft for this same identity so retail/wholesale don't clash forever
        self::forgetMatchingIdentity(
            $payload['item_name'] ?? null,
            $payload['brand'] ?? null,
            $payload['price_type'] ?? null
        );

        Cache::put(self::cacheKey($barcode), array_merge($payload, [
            'barcode_value' => $barcode,
            'queued_at' => now()->toDateTimeString(),
        ]), self::TTL_SECONDS);
        self::registerKey($barcode);
    }

    public static function forgetMatchingIdentity(?string $name, ?string $brand, $priceType): void
    {
        $name = ItemInventoryLinker::searchableText($name);
        $brand = ItemInventoryLinker::searchableText($brand);
        $type = ItemInventoryLinker::normalizePriceType($priceType);

        foreach (self::allKeys() as $key) {
            $draft = self::get($key);
            if (! $draft) {
                continue;
            }

            $sameName = ItemInventoryLinker::searchableText($draft['item_name'] ?? null) === $name;
            $sameBrand = ItemInventoryLinker::searchableText($draft['brand'] ?? null) === $brand;
            $sameType = ItemInventoryLinker::priceTypesMatch($draft['price_type'] ?? null, $type);

            if ($sameName && $sameBrand && $sameType) {
                self::forget($key);
            }
        }
    }

    public static function get(string $barcode): ?array
    {
        $barcode = trim($barcode);
        if ($barcode === '') {
            return null;
        }

        $draft = Cache::get(self::cacheKey($barcode));

        return is_array($draft) ? $draft : null;
    }

    public static function forget(string $barcode): void
    {
        $barcode = trim($barcode);
        Cache::forget(self::cacheKey($barcode));
        self::unregisterKey($barcode);
    }

    public static function allKeys(): array
    {
        $keys = Cache::get(self::indexCacheKey(), []);

        return is_array($keys) ? array_values(array_unique($keys)) : [];
    }

    public static function isBarcodeTaken(string $barcode, ?string $exceptBarcode = null): bool
    {
        $barcode = strtolower(trim($barcode));
        if ($barcode === '') {
            return false;
        }

        foreach (self::allKeys() as $key) {
            if ($exceptBarcode !== null && strtolower(trim($exceptBarcode)) === strtolower(trim($key))) {
                continue;
            }
            if (strtolower(trim($key)) === $barcode) {
                return true;
            }
        }

        return false;
    }

    private static function indexCacheKey(): string
    {
        return self::CACHE_PREFIX.'__index';
    }

    private static function registerKey(string $barcode): void
    {
        $keys = self::allKeys();
        $keys[] = trim($barcode);
        Cache::put(self::indexCacheKey(), array_values(array_unique($keys)), self::TTL_SECONDS);
    }

    private static function unregisterKey(string $barcode): void
    {
        $barcode = trim($barcode);
        $keys = array_values(array_filter(
            self::allKeys(),
            fn ($key) => strtolower(trim((string) $key)) !== strtolower($barcode)
        ));
        Cache::put(self::indexCacheKey(), $keys, self::TTL_SECONDS);
    }

    /**
     * Payload shaped like barcodes/lookup barcode object.
     */
    public static function toLookupBarcode(array $draft): array
    {
        return [
            'id' => null,
            'barcode_value' => $draft['barcode_value'] ?? null,
            'item_name' => $draft['item_name'] ?? null,
            'price' => $draft['price'] ?? null,
            'original_price' => $draft['original_price'] ?? null,
            'price_type' => $draft['price_type'] ?? null,
            'unit' => $draft['unit'] ?? 'pcs',
            'barcode_type' => $draft['barcode_type'] ?? 'CODE128',
            'expiration_date' => $draft['expiration_date'] ?? null,
            'mfg_date' => $draft['mfg_date'] ?? null,
            'active_status' => $draft['active_status'] ?? 'Active',
            'description' => $draft['description'] ?? null,
            'quantity_on_hand' => $draft['quantity_on_hand'] ?? 0,
            'item_image' => $draft['item_image_path'] ?? null,
            'brand' => $draft['brand'] ?? null,
            'lot_number' => $draft['lot_number'] ?? null,
            'pending' => true,
        ];
    }

    public static function toLookupSimple(array $draft): array
    {
        return [
            'found' => true,
            'pending' => true,
            'id' => null,
            'item_name' => $draft['item_name'] ?? null,
            'price' => $draft['price'] ?? null,
            'original_price' => $draft['original_price'] ?? null,
            'price_type' => $draft['price_type'] ?? null,
            'unit' => $draft['unit'] ?? 'pcs',
            'barcode_value' => $draft['barcode_value'] ?? null,
            'barcode_type' => $draft['barcode_type'] ?? 'CODE128',
            'expiration_date' => $draft['expiration_date'] ?? null,
            'mfg_date' => $draft['mfg_date'] ?? null,
            'active_status' => $draft['active_status'] ?? 'Active',
            'description' => $draft['description'] ?? null,
            'quantity_on_hand' => $draft['quantity_on_hand'] ?? 0,
            'item_image' => $draft['item_image_path'] ?? null,
            'brand' => $draft['brand'] ?? null,
        ];
    }
}
