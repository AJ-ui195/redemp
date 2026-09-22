<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ItemList;
use App\Http\Controllers\CashierDashboardController;

class CheckCashierFaceMaskItems extends Command
{
    protected $signature = 'check:cashier-face-mask-items';
    protected $description = 'Check which face mask items from item_lists are being sent to cashier';

    public function handle()
    {
        $this->info('Checking face mask items in item_lists and what cashier would receive...');
        $this->newLine();

        // Get all face mask items from item_lists
        $itemLists = ItemList::whereRaw('LOWER(item) LIKE ?', ['%face mask%'])
            ->orderBy('item')
            ->get();

        $this->info("Total face mask items in item_lists: {$itemLists->count()}");
        $this->newLine();

        // Simulate what CashierDashboardController does
        $allProducts = collect();
        
        foreach ($itemLists as $itemList) {
            $itemName = trim($itemList->item);
            
            // Skip items with empty names
            if (empty($itemName)) {
                $this->warn("Skipping item with empty name - ID: {$itemList->id}");
                continue;
            }
            
            $product = [
                'id' => $itemList->id,
                'name' => $itemName,
                'item' => $itemName,
                'price' => $itemList->price ?? 0,
                'stock_quantity' => (float) ($itemList->quantity_on_hand ?? 0),
                'quantity_on_hand' => (float) ($itemList->quantity_on_hand ?? 0),
                'category' => $itemList->category ?? null,
                'sku' => $itemList->mpn ?? 'N/A',
                'mpn' => $itemList->mpn ?? 'N/A',
                'unit' => $itemList->unit ?? 'pcs',
                'unit_of_measure' => $itemList->unit_of_measure ?? 'pcs',
                'min_stock_level' => $itemList->min_stock_level ?? 10,
                'reorder_pt_min' => $itemList->min_stock_level ?? 10,
                'expiry_date' => $itemList->expiry_date ?? null,
                'price_type' => $itemList->price_type ?? null,
                'image' => null,
                'brand' => $itemList->brand ?? null,
                'description' => $itemList->description ?? null,
            ];
            
            $allProducts->push($product);
        }

        $this->info("Items that would be sent to cashier: {$allProducts->count()}");
        $this->newLine();

        // Show all items
        $this->info("All face mask items that would be in cashier:");
        $this->table(
            ['ID', 'Name', 'Price', 'Stock', 'Has Name', 'Has Description'],
            $allProducts->map(function($p) {
                return [
                    $p['id'],
                    $p['name'],
                    $p['price'],
                    $p['stock_quantity'],
                    $p['name'] ? 'Yes' : 'No',
                    $p['description'] ? 'Yes' : 'No',
                ];
            })->toArray()
        );

        // Check for duplicate IDs
        $ids = $allProducts->pluck('id')->toArray();
        $duplicateIds = array_diff_assoc($ids, array_unique($ids));
        
        if (count($duplicateIds) > 0) {
            $this->newLine();
            $this->warn("Found duplicate IDs that would cause overwriting:");
            $this->table(
                ['ID'],
                array_map(function($id) {
                    return [$id];
                }, array_unique($duplicateIds))
            );
        } else {
            $this->newLine();
            $this->info("No duplicate IDs found.");
        }

        // Check for items with null/empty names that would be filtered
        $itemsWithEmptyNames = $itemLists->filter(function($item) {
            return empty(trim($item->item));
        });

        if ($itemsWithEmptyNames->count() > 0) {
            $this->newLine();
            $this->warn("Found {$itemsWithEmptyNames->count()} items with empty names that would be skipped:");
            $this->table(
                ['ID', 'Item Name'],
                $itemsWithEmptyNames->map(function($item) {
                    return [$item->id, $item->item ?? '(empty)'];
                })->toArray()
            );
        }

        return 0;
    }
}
