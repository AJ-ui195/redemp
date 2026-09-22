<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryProduct;
use App\Models\ItemList;

class DiagnoseFaceMaskDiscrepancy extends Command
{
    protected $signature = 'diagnose:face-mask-discrepancy';
    protected $description = 'Diagnose why item_lists has 22 face mask items but inventory_products has only 20';

    public function handle()
    {
        $this->info('Diagnosing face mask item discrepancy...');
        $this->newLine();

        // Get all face mask items from both tables
        $inventoryProducts = InventoryProduct::whereRaw('LOWER(item_name) LIKE ?', ['%face mask%'])
            ->orderBy('item_name')
            ->get();
        
        $itemLists = ItemList::whereRaw('LOWER(item) LIKE ?', ['%face mask%'])
            ->orderBy('item')
            ->get();

        $this->info("Inventory Products: {$inventoryProducts->count()} items");
        $this->info("Item Lists: {$itemLists->count()} items");
        $this->newLine();

        // Show all items from inventory_products
        $this->info("Items in inventory_products:");
        $this->table(
            ['ID', 'Item Name'],
            $inventoryProducts->map(function($item) {
                return [$item->id, $item->item_name];
            })->toArray()
        );

        $this->newLine();
        $this->info("Items in item_lists:");
        $this->table(
            ['ID', 'Item Name'],
            $itemLists->map(function($item) {
                return [$item->id, $item->item];
            })->toArray()
        );

        // Create arrays of lowercase names
        $invProductNames = $inventoryProducts->map(function($item) {
            return strtolower(trim($item->item_name));
        })->toArray();

        $itemListNames = $itemLists->map(function($item) {
            return strtolower(trim($item->item));
        })->toArray();

        // Find items in item_lists that don't have matching inventory_products
        $missingInInventory = [];
        foreach ($itemLists as $itemList) {
            $itemNameLower = strtolower(trim($itemList->item));
            if (!in_array($itemNameLower, $invProductNames)) {
                $missingInInventory[] = [
                    'id' => $itemList->id,
                    'item' => $itemList->item,
                    'mpn' => $itemList->mpn,
                ];
            }
        }

        if (count($missingInInventory) > 0) {
            $this->newLine();
            $this->warn("Found " . count($missingInInventory) . " items in item_lists that DON'T have matching inventory_products:");
            $this->table(
                ['ID', 'Item Name', 'MPN'],
                array_map(function($item) {
                    return [
                        $item['id'],
                        $item['item'],
                        $item['mpn'] ?? 'N/A',
                    ];
                }, $missingInInventory)
            );
        } else {
            $this->newLine();
            $this->info("All item_lists items have matching inventory_products (by name).");
        }

        // Check for duplicate names (case-insensitive)
        $this->newLine();
        $this->info("Checking for duplicate names (case-insensitive)...");
        
        $nameCounts = [];
        foreach ($itemLists as $itemList) {
            $nameLower = strtolower(trim($itemList->item));
            if (!isset($nameCounts[$nameLower])) {
                $nameCounts[$nameLower] = [];
            }
            $nameCounts[$nameLower][] = [
                'id' => $itemList->id,
                'name' => $itemList->item,
            ];
        }

        $duplicates = [];
        foreach ($nameCounts as $nameLower => $items) {
            if (count($items) > 1) {
                $duplicates[$nameLower] = $items;
            }
        }

        if (count($duplicates) > 0) {
            $this->warn("Found duplicate names in item_lists:");
            foreach ($duplicates as $nameLower => $items) {
                $this->line("  - '{$nameLower}' appears " . count($items) . " times:");
                foreach ($items as $item) {
                    $this->line("    • ID: {$item['id']}, Name: '{$item['name']}'");
                }
            }
        } else {
            $this->info("No duplicate names found in item_lists.");
        }

        return 0;
    }
}
