<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryProduct;
use App\Models\ItemList;
use Illuminate\Support\Facades\DB;

class DiagnoseFaceMaskItems extends Command
{
    protected $signature = 'diagnose:face-mask-items';
    protected $description = 'Diagnose why some face mask items are not showing in cashier';

    public function handle()
    {
        $this->info('Diagnosing face mask items...');
        $this->newLine();

        // Get all face mask items from inventory_products
        $inventoryProducts = InventoryProduct::whereRaw('LOWER(item_name) LIKE ?', ['%face mask%'])->get();
        
        // Get all face mask items from item_lists
        $itemLists = ItemList::whereRaw('LOWER(item) LIKE ?', ['%face mask%'])->get();

        $this->info("Found {$inventoryProducts->count()} items in inventory_products");
        $this->info("Found {$itemLists->count()} items in item_lists");
        $this->newLine();

        // Check which inventory_products don't have matching item_lists
        $missingInItemLists = [];
        foreach ($inventoryProducts as $invProduct) {
            $itemName = trim($invProduct->item_name);
            $itemNameLower = strtolower($itemName);
            
            $matching = $itemLists->first(function($item) use ($itemNameLower) {
                return strtolower(trim($item->item)) === $itemNameLower;
            });

            if (!$matching) {
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
            $this->info("All inventory_products have matching item_lists entries.");
        }

        // Check for case sensitivity issues
        $this->newLine();
        $this->info("Checking for case sensitivity issues...");
        
        $caseIssues = [];
        foreach ($inventoryProducts as $invProduct) {
            $itemName = trim($invProduct->item_name);
            $itemNameLower = strtolower($itemName);
            
            // Find items with same lowercase name but different casing
            $similarItems = $itemLists->filter(function($item) use ($itemNameLower) {
                return strtolower(trim($item->item)) === $itemNameLower;
            });

            if ($similarItems->count() > 0) {
                foreach ($similarItems as $similarItem) {
                    if (trim($similarItem->item) !== $itemName) {
                        $caseIssues[] = [
                            'inventory_product' => $itemName,
                            'item_list' => $similarItem->item,
                        ];
                    }
                }
            }
        }

        if (count($caseIssues) > 0) {
            $this->warn("Found case sensitivity differences:");
            $this->table(
                ['Inventory Product Name', 'Item List Name'],
                $caseIssues
            );
        } else {
            $this->info("No case sensitivity issues found.");
        }

        // Check active status
        $this->newLine();
        $this->info("Checking active status...");
        
        $inactiveItems = $inventoryProducts->filter(function($item) {
            return strtolower($item->active_status ?? '') !== 'active' && $item->active_status !== '1' && $item->active_status !== 1;
        });

        if ($inactiveItems->count() > 0) {
            $this->warn("Found {$inactiveItems->count()} inactive items in inventory_products:");
            $this->table(
                ['ID', 'Item Name', 'Active Status'],
                $inactiveItems->map(function($item) {
                    return [$item->id, $item->item_name, $item->active_status ?? 'NULL'];
                })->toArray()
            );
        } else {
            $this->info("All items are active.");
        }

        return Command::SUCCESS;
    }
}
