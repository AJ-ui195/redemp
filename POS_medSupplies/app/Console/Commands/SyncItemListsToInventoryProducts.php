<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryProduct;
use App\Models\ItemList;
use Illuminate\Support\Facades\DB;

class SyncItemListsToInventoryProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync-from-item-lists 
                            {--dry-run : Show what would be synced without actually syncing}
                            {--update : Update existing items instead of skipping}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync items from item_lists to inventory_products (create missing items in inventory_products)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $update = $this->option('update');

        $this->info('Syncing item_lists to ensure all items exist in inventory_products...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($update) {
            $this->warn('--update will not copy quantity_on_hand or selling price onto existing inventory rows.');
        }

        // Get all item_lists
        $itemLists = ItemList::all();
        
        // Get all inventory_products for matching
        $inventoryProducts = InventoryProduct::all();
        
        // Create array of inventory_product names (lowercase) for quick lookup
        $invProductNames = $inventoryProducts->mapWithKeys(function($item) {
            return [strtolower(trim($item->item_name)) => $item];
        });

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        $progressBar = $this->output->createProgressBar($itemLists->count());
        $progressBar->start();

        foreach ($itemLists as $itemList) {
            try {
                $itemName = trim($itemList->item);
                
                if (empty($itemName)) {
                    $totalSkipped++;
                    $progressBar->advance();
                    continue;
                }

                $itemNameLower = strtolower($itemName);

                // Check if item exists in inventory_products (case-insensitive)
                $existingProduct = $invProductNames[$itemNameLower] ?? null;

                if ($existingProduct) {
                    if ($update) {
                        // Update existing product with data from item_list
                        if (!$dryRun) {
                            $existingProduct->update([
                                'item_name' => $itemName,
                                'description' => $itemList->description ?? $existingProduct->description,
                                'price_type' => $itemList->price_type ?? $existingProduct->price_type,
                                'unit' => $itemList->unit_of_measure ?? $existingProduct->unit,
                                'active_status' => $itemList->active_status ?? $existingProduct->active_status,
                                'item_image' => $itemList->item_image ?? $existingProduct->item_image,
                                'expiration_date' => $itemList->expiry_date ?? $existingProduct->expiration_date,
                                'mfg_date' => $itemList->mfg_date ?? $existingProduct->mfg_date,
                                'brand' => $itemList->brand ?? $existingProduct->brand,
                                'lot_number' => $itemList->lot_number ?? $existingProduct->lot_number,
                            ]);
                        }
                        $totalUpdated++;
                    } else {
                        $totalSkipped++;
                    }
                } else {
                    // Item doesn't exist in inventory_products - create it
                    if (!$dryRun) {
                        // Generate a unique barcode_value if mpn exists, otherwise create one
                        $barcodeValue = $itemList->mpn ?? 'ITEM-' . strtoupper(substr(md5($itemName . time()), 0, 8));
                        
                        // Ensure barcode_value is unique
                        $counter = 1;
                        $originalBarcode = $barcodeValue;
                        while (InventoryProduct::where('barcode_value', $barcodeValue)->exists()) {
                            $barcodeValue = $originalBarcode . '-' . $counter;
                            $counter++;
                        }
                        
                        InventoryProduct::create([
                            'item_name' => $itemName,
                            'barcode_value' => $barcodeValue,
                            'description' => $itemList->description,
                            'price' => $itemList->price ?? 0,
                            'price_type' => $itemList->price_type,
                            'unit' => $itemList->unit_of_measure ?? 'pcs',
                            'quantity_on_hand' => \App\Support\ItemInventoryLinker::quantityForNewRow($itemList->quantity_on_hand),
                            'active_status' => $itemList->active_status ?? 'Active',
                            'item_image' => $itemList->item_image,
                            'expiration_date' => $itemList->expiry_date,
                            'mfg_date' => $itemList->mfg_date,
                            'brand' => $itemList->brand,
                            'lot_number' => $itemList->lot_number,
                        ]);
                        
                        // Add to lookup array for subsequent checks
                        $invProductNames[$itemNameLower] = (object)['item_name' => $itemName];
                    }
                    $totalCreated++;
                }
            } catch (\Exception $e) {
                $this->error("Error processing {$itemList->item}: " . $e->getMessage());
                $totalErrors++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Sync Summary:');
        $summary = [
            ['Created in inventory_products', $totalCreated],
            ['Updated in inventory_products', $totalUpdated],
            ['Already existed (skipped)', $totalSkipped],
            ['Total Processed', $itemLists->count()],
        ];
        
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
            $this->info('All items in item_lists now have corresponding entries in inventory_products.');
        }

        return 0;
    }
}
