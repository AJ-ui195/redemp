<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryProduct;
use App\Models\ItemList;
use App\Http\Controllers\CashierDashboardController;

class CompareFaceMaskItems extends Command
{
    protected $signature = 'compare:face-mask-items';
    protected $description = 'Compare face mask items between inventory and cashier';

    public function handle()
    {
        $this->info('Comparing face mask items...');
        $this->newLine();

        // Get items from inventory_products
        $inventoryProducts = InventoryProduct::whereRaw('LOWER(item_name) LIKE ?', ['%face mask%'])
            ->orderBy('item_name')
            ->get();
        
        // Get items from item_lists
        $itemLists = ItemList::whereRaw('LOWER(item) LIKE ?', ['%face mask%'])
            ->orderBy('item')
            ->get();

        $this->info("Inventory Products: {$inventoryProducts->count()} items");
        $this->info("Item Lists: {$itemLists->count()} items");
        $this->newLine();

        // Simulate what CashierDashboardController does
        $controller = new CashierDashboardController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('index');
        
        // We can't easily call the private method, so let's replicate the logic
        $processedItemNames = [];
        $allProducts = collect();
        
        // Process inventory_products first (matching CashierDashboardController logic)
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
            
            $allProducts->push([
                'id' => $matchingItemList ? $matchingItemList->id : $invProduct->id,
                'name' => $itemName,
                'source' => 'inventory_products',
                'has_item_list_match' => $matchingItemList ? 'yes' : 'no',
            ]);
            
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
            
            $allProducts->push([
                'id' => $itemList->id,
                'name' => $itemName,
                'source' => 'item_lists_only',
                'has_item_list_match' => 'N/A',
            ]);
            
            $processedItemNames[] = $itemNameLower;
        }

        $this->info("Cashier would show: {$allProducts->count()} items");
        $this->newLine();

        // Show all items that would be in cashier
        $this->info("Items that would appear in cashier:");
        $this->table(
            ['ID', 'Name', 'Source', 'Has Item List Match'],
            $allProducts->map(function($p) {
                return [
                    $p['id'],
                    $p['name'],
                    $p['source'],
                    $p['has_item_list_match'],
                ];
            })->toArray()
        );

        // Find items in inventory_products that are NOT in cashier list
        $cashierNames = $allProducts->pluck('name')->map(function($name) {
            return strtolower(trim($name));
        })->toArray();

        $missingItems = [];
        foreach ($inventoryProducts as $invProduct) {
            $itemName = trim($invProduct->item_name);
            $itemNameLower = strtolower($itemName);
            
            if (!in_array($itemNameLower, $cashierNames)) {
                $missingItems[] = [
                    'id' => $invProduct->id,
                    'item_name' => $invProduct->item_name,
                    'barcode_value' => $invProduct->barcode_value,
                    'active_status' => $invProduct->active_status,
                ];
            }
        }

        if (count($missingItems) > 0) {
            $this->newLine();
            $this->warn("Found " . count($missingItems) . " items in inventory_products that are NOT in cashier list:");
            $this->table(
                ['ID', 'Item Name', 'Barcode', 'Active Status'],
                array_map(function($item) {
                    return [
                        $item['id'],
                        $item['item_name'],
                        $item['barcode_value'] ?? 'N/A',
                        $item['active_status'],
                    ];
                }, $missingItems)
            );
        } else {
            $this->newLine();
            $this->info("All inventory_products items are in the cashier list.");
        }

        // Also check item_lists items that are missing
        $missingItemLists = [];
        foreach ($itemLists as $itemList) {
            $itemName = trim($itemList->item);
            $itemNameLower = strtolower($itemName);
            
            if (!in_array($itemNameLower, $cashierNames)) {
                $missingItemLists[] = [
                    'id' => $itemList->id,
                    'item' => $itemList->item,
                    'mpn' => $itemList->mpn,
                    'active_status' => $itemList->active_status,
                ];
            }
        }

        if (count($missingItemLists) > 0) {
            $this->newLine();
            $this->warn("Found " . count($missingItemLists) . " items in item_lists that are NOT in cashier list:");
            $this->table(
                ['ID', 'Item Name', 'MPN', 'Active Status'],
                array_map(function($item) {
                    return [
                        $item['id'],
                        $item['item'],
                        $item['mpn'] ?? 'N/A',
                        $item['active_status'] ?? 'NULL',
                    ];
                }, $missingItemLists)
            );
        }

        return 0;
    }
}
