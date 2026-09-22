<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryProduct;
use App\Models\ItemList;
use Illuminate\Support\Facades\DB;

class SyncInventoryProductsToItemLists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync-to-item-lists 
                            {--update : Update existing items instead of skipping}
                            {--dry-run : Show what would be synced without actually syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync items from inventory_products to item_lists (creates missing items in item_lists)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $update = $this->option('update');
        $dryRun = $this->option('dry-run');

        $this->info('Starting sync from inventory_products to item_lists...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($update) {
            $this->warn('--update will not copy quantity_on_hand or selling price onto existing item_list rows.');
        }

        // Get all inventory products
        $inventoryProducts = InventoryProduct::all();
        
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

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

                // Check if item exists in item_lists (case-insensitive match)
                $existingItem = ItemList::whereRaw('LOWER(TRIM(item)) = ?', [strtolower($itemName)])->first();

                if ($existingItem) {
                    if ($update) {
                        if (!$dryRun) {
                            // Update existing item with data from inventory_product
                            $existingItem->skipObserverSync = true; // Prevent observer from syncing back
                            
                            $existingItem->update([
                                'item' => $itemName,
                                'description' => $invProduct->description ?? $existingItem->description,
                                'price_type' => $invProduct->price_type ?? $existingItem->price_type,
                                'unit_of_measure' => $invProduct->unit ?? $existingItem->unit_of_measure,
                                'active_status' => $invProduct->active_status ?? $existingItem->active_status,
                                'item_image' => $invProduct->item_image ?? $existingItem->item_image,
                                'expiry_date' => $invProduct->expiration_date ?? $existingItem->expiry_date,
                                'mfg_date' => $invProduct->mfg_date ?? $existingItem->mfg_date,
                                'brand' => $invProduct->brand ?? $existingItem->brand,
                                'lot_number' => $invProduct->lot_number ?? $existingItem->lot_number,
                                'mpn' => $invProduct->barcode_value ?? $existingItem->mpn,
                            ]);
                            
                            $existingItem->skipObserverSync = false;
                        }
                        $totalUpdated++;
                    } else {
                        $totalSkipped++;
                    }
                } else {
                    // Create new item in item_lists
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

        // Summary
        $this->info('Sync Summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Created', $totalCreated],
                ['Updated', $totalUpdated],
                ['Skipped', $totalSkipped],
                ['Errors', $totalErrors],
                ['Total Processed', $inventoryProducts->count()],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. No changes were made.');
        } else {
            $this->info('Sync completed successfully!');
        }

        return Command::SUCCESS;
    }
}
