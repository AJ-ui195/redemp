<?php

namespace App\Console\Commands;

use App\Models\ItemList;
use App\Models\Barcode;
use Illuminate\Console\Command;

class SyncItemImagesFromBarcodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'items:sync-images {--force : Force sync even if item already has an image}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync item images from barcodes table to item_lists table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting image sync from barcodes to item_lists...');
        
        $force = $this->option('force');
        $syncedCount = 0;
        $skippedCount = 0;
        $notFoundCount = 0;

        // Get all items with their barcodes
        $query = ItemList::with('barcode');
        
        if (!$force) {
            // Only get items without images if not forcing
            $query->whereNull('item_image')->orWhere('item_image', '');
        }
        
        $items = $query->get();
        $totalItems = $items->count();

        $this->info("Found {$totalItems} items to process.");

        $progressBar = $this->output->createProgressBar($totalItems);
        $progressBar->start();

        foreach ($items as $item) {
            // Skip if item already has an image and not forcing
            if (!$force && !empty($item->item_image)) {
                $skippedCount++;
                $progressBar->advance();
                continue;
            }

            // Check if there's a barcode with an image for this item
            $barcode = $item->barcode;
            
            if (!$barcode) {
                // Try to find barcode by mpn (SKU)
                if ($item->mpn) {
                    $barcode = Barcode::where('barcode_value', $item->mpn)->first();
                }
            }

            if ($barcode && $barcode->item_image) {
                $item->item_image = $barcode->item_image;
                $item->save();
                $syncedCount++;
            } else {
                $notFoundCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Sync completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Synced', $syncedCount],
                ['Skipped (already has image)', $skippedCount],
                ['No barcode image found', $notFoundCount],
                ['Total processed', $totalItems],
            ]
        );

        return Command::SUCCESS;
    }
}
