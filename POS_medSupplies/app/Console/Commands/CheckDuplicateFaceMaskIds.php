<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryProduct;
use App\Models\ItemList;

class CheckDuplicateFaceMaskIds extends Command
{
    protected $signature = 'check:duplicate-face-mask-ids';
    protected $description = 'Check for duplicate IDs in face mask items that would cause overwriting';

    public function handle()
    {
        $this->info('Checking for duplicate IDs in face mask items...');
        $this->newLine();

        // Get items from inventory_products
        $inventoryProducts = InventoryProduct::whereRaw('LOWER(item_name) LIKE ?', ['%face mask%'])
            ->orderBy('item_name')
            ->get();
        
        // Get items from item_lists
        $itemLists = ItemList::whereRaw('LOWER(item) LIKE ?', ['%face mask%'])
            ->orderBy('item')
            ->get();

        // Simulate CashierDashboardController logic
        $processedItemNames = [];
        $productsByKey = [];
        $duplicateKeys = [];

        // Process inventory_products first
        foreach ($inventoryProducts as $invProduct) {
            $itemName = trim($invProduct->item_name);
            
            if (empty($itemName)) {
                continue;
            }
            
            $itemNameLower = strtolower($itemName);
            
            if (in_array($itemNameLower, $processedItemNames)) {
                continue;
            }
            
            $matchingItemList = $itemLists->first(function($item) use ($itemName) {
                return strtolower(trim($item->item)) === strtolower($itemName);
            });
            
            // This is the key that would be used in JavaScript: products[productId]
            $productId = $matchingItemList ? $matchingItemList->id : $invProduct->id;
            
            if (isset($productsByKey[$productId])) {
                $duplicateKeys[] = [
                    'id' => $productId,
                    'existing_name' => $productsByKey[$productId]['name'],
                    'new_name' => $itemName,
                    'existing_source' => $productsByKey[$productId]['source'],
                    'new_source' => 'inventory_products',
                ];
            } else {
                $productsByKey[$productId] = [
                    'id' => $productId,
                    'name' => $itemName,
                    'source' => 'inventory_products',
                ];
            }
            
            $processedItemNames[] = $itemNameLower;
        }
        
        // Process item_lists that don't have matching inventory_products
        foreach ($itemLists as $itemList) {
            $itemName = trim($itemList->item);
            
            if (empty($itemName)) {
                continue;
            }
            
            $itemNameLower = strtolower($itemName);
            
            if (in_array($itemNameLower, $processedItemNames)) {
                continue;
            }
            
            $productId = $itemList->id;
            
            if (isset($productsByKey[$productId])) {
                $duplicateKeys[] = [
                    'id' => $productId,
                    'existing_name' => $productsByKey[$productId]['name'],
                    'new_name' => $itemName,
                    'existing_source' => $productsByKey[$productId]['source'],
                    'new_source' => 'item_lists_only',
                ];
            } else {
                $productsByKey[$productId] = [
                    'id' => $productId,
                    'name' => $itemName,
                    'source' => 'item_lists_only',
                ];
            }
            
            $processedItemNames[] = $itemNameLower;
        }

        $this->info("Total unique product keys: " . count($productsByKey));
        $this->info("Total items processed: " . count($processedItemNames));
        $this->newLine();

        if (count($duplicateKeys) > 0) {
            $this->warn("Found " . count($duplicateKeys) . " duplicate IDs that would cause overwriting:");
            $this->table(
                ['ID', 'Existing Name', 'New Name', 'Existing Source', 'New Source'],
                array_map(function($item) {
                    return [
                        $item['id'],
                        $item['existing_name'],
                        $item['new_name'],
                        $item['existing_source'],
                        $item['new_source'],
                    ];
                }, $duplicateKeys)
            );
            $this->newLine();
            $this->error("These items would overwrite each other in the JavaScript products object!");
        } else {
            $this->info("No duplicate IDs found. All items should appear correctly.");
        }

        // Show all products that would be in the final object
        $this->newLine();
        $this->info("Final products that would be in JavaScript object:");
        $this->table(
            ['ID', 'Name', 'Source'],
            array_map(function($p) {
                return [
                    $p['id'],
                    $p['name'],
                    $p['source'],
                ];
            }, array_values($productsByKey))
        );

        return 0;
    }
}
