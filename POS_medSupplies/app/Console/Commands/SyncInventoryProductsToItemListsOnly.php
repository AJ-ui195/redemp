<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryProduct;
use App\Models\ItemList;
use Illuminate\Support\Facades\DB;

class SyncInventoryProductsToItemListsOnly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync-to-item-lists-only 
                            {--dry-run : Show what would be synced without actually syncing}
                            {--delete-orphans : Delete items from inventory_products that don\'t have matching item_lists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure all items in inventory_products have corresponding entries in item_lists (create missing items in item_lists)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $deleteOrphans = $this->option('delete-orphans');

        $this->info('Syncing inventory_products to ensure all items exist in item_lists...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get all inventory products
        $inventoryProducts = InventoryProduct::all();
        
        // Get all item_lists for matching
        $itemLists = ItemList::all();
        
        // Create array of item_list names (lowercase) for quick lookup
        $itemListNames = $itemLists->mapWithKeys(function($item) {
            return [strtolower(trim($item->item)) => $item];
        });

        $totalCreated = 0;
        $totalSkipped = 0;
        $totalDeleted = 0;
        $totalErrors = 0;
        $orphanItems = [];

        $progressBar = $this->output->createProgressBar($inventoryProducts->count());
        $progressBar->start();

        foreach ($inventoryProducts as $invProduct) {
            try {
                $itemName = trim($invProduct->item_name);
                
                if (empty($itemName)) {
                    $totalSkipped++;
                    $progressBar->advance();
                    continue;
                }

                $itemNameLower = strtolower($itemName);

                // Check if item exists in item_lists (case-insensitive)
                $existingItem = $itemListNames[$itemNameLower] ?? null;

                if ($existingItem) {
                    // Item already exists in item_lists - skip
                    $totalSkipped++;
                } else {
                    // Item doesn't exist in item_lists - create it
                    if (!$dryRun) {
                        ItemList::create([
                            'item' => $itemName,
                            'description' => $invProduct->description,
                            'price' => $invProduct->price ?? 0,
                            'price_type' => $invProduct->price_type,
                            'unit_of_measure' => $invProduct->unit ?? 'pcs',
                            'quantity_on_hand' => \App\Support\ItemInventoryLinker::quantityForNewRow($invProduct->quantity_on_hand),
                            'active_status' => $invProduct->active_status ?? 'Active',
                            'item_image' => $invProduct->item_image,
                            'expiry_date' => $invProduct->expiration_date,
                            'mfg_date' => $invProduct->mfg_date,
                            'brand' => $invProduct->brand,
                            'lot_number' => $invProduct->lot_number,
                            'mpn' => $invProduct->barcode_value,
                            'reorder_pt_min' => $invProduct->min_stock_level ?? 10,
                        ]);
                        
                        // Add to lookup array for subsequent checks
                        $itemListNames[$itemNameLower] = (object)['item' => $itemName];
                    }
                    $totalCreated++;
                }
            } catch (\Exception $e) {
                $this->error("Error processing {$invProduct->item_name}: " . $e->getMessage());
                $totalErrors++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // If delete-orphans option is set, find and delete items in inventory_products that don't have matches
        if ($deleteOrphans) {
            $this->newLine();
            $this->info('Checking for orphaned items in inventory_products...');
            
            // Refresh item_lists after creating new ones
            if (!$dryRun) {
                $itemLists = ItemList::all();
                $itemListNames = $itemLists->mapWithKeys(function($item) {
                    return [strtolower(trim($item->item)) => $item];
                });
            }
            
            foreach ($inventoryProducts as $invProduct) {
                $itemName = trim($invProduct->item_name);
                
                if (empty($itemName)) {
                    continue;
                }
                
                $itemNameLower = strtolower($itemName);
                
                // Check if item exists in item_lists
                if (!isset($itemListNames[$itemNameLower])) {
                    $orphanItems[] = [
                        'id' => $invProduct->id,
                        'item_name' => $invProduct->item_name,
                        'barcode_value' => $invProduct->barcode_value,
                    ];
                    
                    if (!$dryRun) {
                        try {
                            $invProduct->delete();
                            $totalDeleted++;
                        } catch (\Exception $e) {
                            $this->error("Error deleting {$invProduct->item_name}: " . $e->getMessage());
                            $totalErrors++;
                        }
                    }
                }
            }
            
            if (count($orphanItems) > 0) {
                if ($dryRun) {
                    $this->warn("Found " . count($orphanItems) . " orphaned items that would be deleted:");
                } else {
                    $this->warn("Deleted " . count($orphanItems) . " orphaned items from inventory_products:");
                }
                $this->table(
                    ['ID', 'Item Name', 'Barcode'],
                    array_map(function($item) {
                        return [
                            $item['id'],
                            $item['item_name'],
                            $item['barcode_value'] ?? 'N/A',
                        ];
                    }, $orphanItems)
                );
            } else {
                $this->info("No orphaned items found. All inventory_products have matching item_lists entries.");
            }
        }

        // Summary
        $this->newLine();
        $this->info('Sync Summary:');
        $summary = [
            ['Created in item_lists', $totalCreated],
            ['Already existed (skipped)', $totalSkipped],
            ['Total Processed', $inventoryProducts->count()],
        ];
        
        if ($deleteOrphans) {
            $summary[] = ['Deleted from inventory_products', $totalDeleted];
        }
        
        if ($totalErrors > 0) {
            $summary[] = ['Errors', $totalErrors];
        }
        
        $this->table(
            ['Action', 'Count'],
            $summary
        );

        if ($dryRun) {
            $this->warn('This was a dry run. No changes were made.');
        } else {
            $this->info('Sync completed successfully!');
            $this->info('All items in inventory_products now have corresponding entries in item_lists.');
        }

        return 0;
    }
}
