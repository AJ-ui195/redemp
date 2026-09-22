<?php

namespace App\Support;

/**
 * Legacy helper — old catalogs used item_lists / inventory_products / barcodes.
 * The pos schema stores price on products, so this is intentionally a no-op.
 */
class RepairPlaceholderPrices
{
    /**
     * @return array{inventory: int, item_lists: int, barcodes: int}
     */
    public static function run(): array
    {
        return [
            'inventory' => 0,
            'item_lists' => 0,
            'barcodes' => 0,
        ];
    }
}
