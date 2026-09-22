<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScannedProduct;
use App\Models\ItemList;
use Illuminate\Support\Facades\DB;

class ImportScannedProductsToItemLists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:scanned-products-to-item-lists 
                            {--update : Update existing items instead of skipping}
                            {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import items from scanned_products table to item_lists table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting import of scanned products to item_lists...');
        $this->newLine();

        $update = $this->option('update');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get all scanned products
        $scannedProducts = ScannedProduct::whereNotNull('item_name')
            ->where('item_name', '!=', '')
            ->get();

        if ($scannedProducts->isEmpty()) {
            $this->warn('No scanned products found with item names.');
            return Command::SUCCESS;
        }

        $this->info("Found {$scannedProducts->count()} scanned products with item names.");
        $this->newLine();

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($scannedProducts->count());
        $progressBar->start();

        foreach ($scannedProducts as $scannedProduct) {
            try {
                // Check if item already exists
                $existingItem = ItemList::where('item', $scannedProduct->item_name)->first();

                if ($existingItem) {
                    if ($update) {
                        // Update existing item
                        if (!$dryRun) {
                            $existingItem->update([
                                'description' => $scannedProduct->description ?? $existingItem->description,
                                'unit_of_measure' => $scannedProduct->unit ?? $existingItem->unit_of_measure,
                                'active_status' => $scannedProduct->active_status ?? $existingItem->active_status,
                            ]);
                        }
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    // Create new item
                    if (!$dryRun) {
                        ItemList::create([
                            'item' => $scannedProduct->item_name,
                            'description' => $scannedProduct->description,
                            'price' => $scannedProduct->price,
                            'quantity_on_hand' => \App\Support\ItemInventoryLinker::quantityForNewRow($scannedProduct->quantity_on_hand),
                            'unit_of_measure' => $scannedProduct->unit ?? 'pcs',
                            'active_status' => $scannedProduct->active_status ?? 'Active',
                            'price_type' => null, // Can be set manually later (retail/wholesale)
                            'cost' => null,
                            'reorder_pt_min' => 10, // Default reorder point
                        ]);
                    }
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error processing item '{$scannedProduct->item_name}': " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Import Summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Imported (New)', $imported],
                ['Updated (Existing)', $updated],
                ['Skipped (Existing)', $skipped],
                ['Errors', $errors],
                ['Total Processed', $scannedProducts->count()],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to actually import.');
        } else {
            $this->info('Import completed successfully!');
        }

        return Command::SUCCESS;
    }
}

