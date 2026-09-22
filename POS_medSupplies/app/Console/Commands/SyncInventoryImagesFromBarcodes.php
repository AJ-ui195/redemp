<?php

namespace App\Console\Commands;

use App\Support\BarcodeImageSync;
use Illuminate\Console\Command;

class SyncInventoryImagesFromBarcodes extends Command
{
    protected $signature = 'inventory:sync-images-from-barcodes
                            {--dry-run : Show what would be copied without writing}';

    protected $description = 'Copy barcode photo paths onto matching inventory_products (and empty item_lists). Does not create products or change qty/price.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN - no image paths will be written');
        }

        $result = BarcodeImageSync::run($dryRun);

        foreach ($result['changes'] as $line) {
            $this->line($line);
        }

        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Inventory photos filled', $result['inventory_filled']],
                ['Item list photos filled', $result['item_list_filled']],
                ['Skipped (already has photo)', $result['skipped_has_image']],
                ['Barcode photo with no matching product', $result['skipped_no_match']],
                ['Barcode photo path whose file is missing on disk', $result['missing_files']],
            ]
        );

        if ($result['missing_files'] > 0) {
            $this->warn('Some barcode paths were copied (or would be copied) but the PNG/JPG is not in public/images/barcodes/. Upload that folder or those products will still 404.');
        }

        if ($dryRun) {
            $this->info('Dry run finished. Run without --dry-run to write the image paths.');
        } else {
            $this->info('Done. No products were created. Qty and price were not changed.');
        }

        return self::SUCCESS;
    }
}
