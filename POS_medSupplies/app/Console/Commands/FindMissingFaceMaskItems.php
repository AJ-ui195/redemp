<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryProduct;
use App\Models\ItemList;

class FindMissingFaceMaskItems extends Command
{
    protected $signature = 'find:missing-face-mask-items';
    protected $description = 'Find face mask items that exist in item_lists but not in inventory_products';

    public function handle()
    {
        $this->info('Finding missing face mask items...');
        $this->newLine();

        // Get all face mask items from both tables
        $inventoryProducts = InventoryProduct::whereRaw('LOWER(item_name) LIKE ?', ['%face mask%'])->get();
        $itemLists = ItemList::whereRaw('LOWER(item) LIKE ?', ['%face mask%'])->get();

        // Create array of inventory_product names (lowercase)
        $invProductNames = $inventoryProducts->map(function($item) {
            return strtolower(trim($item->item_name));
        })->toArray();

        // Find item_lists items that don't have matching inventory_products
        $missingItems = [];
        foreach ($itemLists as $itemList) {
            $itemNameLower = strtolower(trim($itemList->item));
            
            if (!in_array($itemNameLower, $invProductNames)) {
                $missingItems[] = [
                    'id' => $itemList->id,
                    'item' => $itemList->item,
                    'mpn' => $itemList->mpn,
                    'active_status' => $itemList->active_status,
                    'quantity_on_hand' => $itemList->quantity_on_hand,
                ];
            }
        }

        if (count($missingItems) > 0) {
            $this->warn("Found " . count($missingItems) . " items in item_lists that don't have matching inventory_products:");
            $this->table(
                ['ID', 'Item Name', 'MPN', 'Active Status', 'Quantity'],
                array_map(function($item) {
                    return [
                        $item['id'],
                        $item['item'],
                        $item['mpn'] ?? 'N/A',
                        $item['active_status'] ?? 'NULL',
                        $item['quantity_on_hand'],
                    ];
                }, $missingItems)
            );
        } else {
            $this->info("All item_lists items have matching inventory_products entries.");
        }

        // Also check the reverse - items in inventory_products that don't have matching item_lists
        $this->newLine();
        $this->info("Checking reverse...");
        
        $itemListNames = $itemLists->map(function($item) {
            return strtolower(trim($item->item));
        })->toArray();

        $missingInItemLists = [];
        foreach ($inventoryProducts as $invProduct) {
            $itemNameLower = strtolower(trim($invProduct->item_name));
            
            if (!in_array($itemNameLower, $itemListNames)) {
                $missingInItemLists[] = [
                    'id' => $invProduct->id,
                    'item_name' => $invProduct->item_name,
                    'barcode_value' => $invProduct->barcode_value,
                    'active_status' => $invProduct->active_status,
                    'quantity_on_hand' => $invProduct->quantity_on_hand,
                ];
            }
        }

        if (count($missingInItemLists) > 0) {
            $this->warn("Found " . count($missingInItemLists) . " items in inventory_products that don't have matching item_lists:");
            $this->table(
                ['ID', 'Item Name', 'Barcode', 'Active Status', 'Quantity'],
                array_map(function($item) {
                    return [
                        $item['id'],
                        $item['item_name'],
                        $item['barcode_value'] ?? 'N/A',
                        $item['active_status'],
                        $item['quantity_on_hand'],
                    ];
                }, $missingInItemLists)
            );
        } else {
            $this->info("All inventory_products items have matching item_lists entries.");
        }

        return 0;
    }
}
