<?php

namespace App\Observers;

use App\Models\ItemList;
use App\Models\InventoryProduct;
use App\Support\ItemInventoryLinker;

class ItemListObserver
{
    /**
     * Handle the ItemList "created" event.
     */
    public function created(ItemList $itemList): void
    {
        // Skip syncing if flag is set
        if ($itemList->skipObserverSync) {
            return;
        }
        
        $this->syncToInventoryProducts($itemList);
    }

    /**
     * Handle the ItemList "updated" event.
     */
    public function updated(ItemList $itemList): void
    {
        // Skip syncing if flag is set (manual edits should not trigger sync)
        if ($itemList->skipObserverSync) {
            \Log::info('ItemListObserver: Skipping sync due to skipObserverSync flag', [
                'item_list_id' => $itemList->id,
                'item_name' => $itemList->item
            ]);
            return;
        }
        
        // Log when observer is triggered (for debugging)
        \Log::info('ItemListObserver: updated() called', [
            'item_list_id' => $itemList->id,
            'item_name' => $itemList->item,
            'skipObserverSync' => $itemList->skipObserverSync ?? false
        ]);
        
        $this->syncToInventoryProducts($itemList);
    }

    /**
     * Handle the ItemList "deleted" event.
     */
    public function deleted(ItemList $itemList): void
    {
        // Optionally delete or mark as inactive in inventory_products
        // For now, we'll just leave it as is to preserve data
    }

    /**
     * Handle the ItemList "restored" event.
     */
    public function restored(ItemList $itemList): void
    {
        $this->syncToInventoryProducts($itemList);
    }

    /**
     * Handle the ItemList "force deleted" event.
     */
    public function forceDeleted(ItemList $itemList): void
    {
        // Optionally delete from inventory_products
    }

    /**
     * Sync item from item_lists to inventory_products
     */
    private function syncToInventoryProducts(ItemList $itemList): void
    {
        // CRITICAL: Skip if flag is set (manual edits should not trigger sync)
        // This is a double-check in case the observer somehow gets called
        if (isset($itemList->skipObserverSync) && $itemList->skipObserverSync) {
            \Log::info('ItemListObserver: syncToInventoryProducts skipped due to flag', [
                'item_list_id' => $itemList->id,
                'item_name' => $itemList->item
            ]);
            return;
        }
        
        // Skip if item name is empty
        if (empty($itemList->item)) {
            return;
        }

        \Log::info('ItemListObserver: syncToInventoryProducts called', [
            'item_list_id' => $itemList->id,
            'item_name' => $itemList->item,
            'mpn' => $itemList->mpn
        ]);

        try {
            // Match by barcode + price_type (or generated ITEM-{id}), never by name alone.
            // Same-name retail/wholesale twins must not overwrite each other's qty/price.
            $existing = ItemInventoryLinker::findInventoryProductForItemList($itemList);

            if ($existing) {
                $this->updateExistingInventoryProduct($existing, $itemList);
            } else {
                InventoryProduct::create(array_merge([
                    'barcode_value' => ItemInventoryLinker::uniqueBarcodeForNewInventoryProduct($itemList),
                    'item_name' => $itemList->item,
                    'description' => $itemList->description,
                    'price' => $itemList->price,
                    'price_type' => $itemList->price_type ?? null,
                    'unit' => $this->normalizeUnit($itemList->unit_of_measure ?? 'pcs'),
                    'quantity_on_hand' => ItemInventoryLinker::quantityForNewRow($itemList->quantity_on_hand),
                    'expiration_date' => $itemList->expiry_date,
                    'mfg_date' => $itemList->mfg_date,
                    'active_status' => $itemList->active_status ?? 'Active',
                    'item_image' => $itemList->item_image,
                    'lot_number' => $itemList->lot_number,
                ], $this->originalPricePayloadFromItemCost($itemList)));
            }
        } catch (\Exception $e) {
            // Log error but don't break the application
            \Log::error('Error syncing ItemList to InventoryProduct', [
                'item_list_id' => $itemList->id,
                'item_name' => $itemList->item,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function updateExistingInventoryProduct(InventoryProduct $existing, ItemList $itemList): void
    {
        $existing->refresh();

        // Inventory qty is the live stock. Never copy item_lists.quantity_on_hand here
        // (null/0 coalescing is how stock was wiped without a real inventory edit).
        $payload = [
            'description' => $itemList->description ?? $existing->description,
            'price_type' => $itemList->price_type ?? $existing->price_type,
            'unit' => $this->normalizeUnit($itemList->unit_of_measure ?? $existing->unit ?? 'pcs'),
            'active_status' => $itemList->active_status ?? $existing->active_status,
            'item_image' => $itemList->item_image ?? $existing->item_image,
            'expiration_date' => $itemList->expiry_date ?? $existing->expiration_date,
            'mfg_date' => $itemList->mfg_date ?? $existing->mfg_date,
            'lot_number' => $itemList->lot_number ?? $existing->lot_number,
        ];

        if ($itemList->wasChanged('price')) {
            $incoming = $itemList->price;
            if (
                !(
                    ItemInventoryLinker::isPlaceholderPrice($incoming)
                    && !ItemInventoryLinker::isPlaceholderPrice($existing->price)
                )
            ) {
                $payload['price'] = $incoming ?? $existing->price;
            }
        }

        $payload = array_merge($payload, $this->originalPricePayloadFromItemCost($itemList));

        $existing->update($payload);
    }

    /**
     * Normalize unit value to handle various formats
     */
    private function normalizeUnit($unit)
    {
        if (empty($unit)) {
            return 'pcs';
        }

        $unit = trim($unit);
        
        // If it's already a simple value, return as is
        if (in_array(strtolower($unit), ['pcs', 'box', 'case', 'pair', 'bottle', 'pack', 'liter', 'gallon', 'roll', 'gal', 'set', 'unit', 'pck', 'pcs/pck', 'pcs/roll', 'piece'])) {
            return strtolower($unit);
        }

        // Extract unit from formats like "PIECE (PC)", "PAIR (PAIR)", etc.
        if (preg_match('/\(([^)]+)\)/', $unit, $matches)) {
            $extracted = strtolower(trim($matches[1]));
            $unitMap = [
                'pc' => 'pcs',
                'pair' => 'pair',
                'btl' => 'bottle',
                'pck' => 'pack',
                'box' => 'box',
                'ltr' => 'liter',
                'gal' => 'gallon',
            ];
            return $unitMap[$extracted] ?? $extracted;
        }

        // Map common variations
        $unitLower = strtolower($unit);
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

        return $unitMap[$unitLower] ?? strtolower($unit);
    }

    /**
     * Map item_lists.cost to inventory_products.original_price when cost is set.
     *
     * @return array<string, mixed>
     */
    private function originalPricePayloadFromItemCost(ItemList $itemList): array
    {
        if ($itemList->cost === null) {
            return [];
        }

        return ['original_price' => $itemList->cost];
    }
}
