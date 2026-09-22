<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryProduct;
use App\Models\Barcode;
use App\Models\ScannedProduct;
use App\Models\ItemList;
use Illuminate\Support\Facades\DB;

class PopulateInventoryProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:populate 
                            {--source=all : Source to import from (barcodes, scanned_products, item_lists, all)}
                            {--update : Update existing products instead of skipping}
                            {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate inventory_products table from barcodes, scanned_products, or item_lists';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $source = $this->option('source');
        $update = $this->option('update');
        $dryRun = $this->option('dry-run');

        $this->info('Starting population of inventory_products table...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $totalImported = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        // Import from barcodes table
        if ($source === 'all' || $source === 'barcodes') {
            $this->info('Importing from barcodes table...');
            $result = $this->importFromBarcodes($update, $dryRun);
            $totalImported += $result['imported'];
            $totalUpdated += $result['updated'];
            $totalSkipped += $result['skipped'];
            $totalErrors += $result['errors'];
            $this->newLine();
        }

        // Import from scanned_products table
        if ($source === 'all' || $source === 'scanned_products') {
            $this->info('Importing from scanned_products table...');
            $result = $this->importFromScannedProducts($update, $dryRun);
            $totalImported += $result['imported'];
            $totalUpdated += $result['updated'];
            $totalSkipped += $result['skipped'];
            $totalErrors += $result['errors'];
            $this->newLine();
        }

        // Import from item_lists table
        if ($source === 'all' || $source === 'item_lists') {
            $this->info('Importing from item_lists table...');
            $result = $this->importFromItemLists($update, $dryRun);
            $totalImported += $result['imported'];
            $totalUpdated += $result['updated'];
            $totalSkipped += $result['skipped'];
            $totalErrors += $result['errors'];
            $this->newLine();
        }

        // Summary
        $this->info('Population Summary:');
        $this->table(
            ['Action', 'Count'],
            [
                ['Imported (New)', $totalImported],
                ['Updated (Existing)', $totalUpdated],
                ['Skipped (Existing)', $totalSkipped],
                ['Errors', $totalErrors],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to actually import.');
        } else {
            $this->info('Population completed successfully!');
        }

        return Command::SUCCESS;
    }

    private function importFromBarcodes($update, $dryRun)
    {
        $barcodes = Barcode::whereNotNull('barcode_value')
            ->whereNotNull('item_name')
            ->where('item_name', '!=', '')
            ->get();

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($barcodes->count());
        $progressBar->start();

        foreach ($barcodes as $barcode) {
            try {
                $existing = InventoryProduct::where('barcode_value', $barcode->barcode_value)->first();

                if ($existing) {
                    if ($update) {
                        if (!$dryRun) {
                            $existing->update([
                                'item_name' => $barcode->item_name,
                                'description' => $barcode->description ?? $existing->description,
                                'price_type' => $barcode->price_type ?? $existing->price_type,
                                'unit' => $barcode->unit ?? $existing->unit,
                                'expiration_date' => $barcode->expiration_date ?? $existing->expiration_date,
                                'active_status' => $barcode->active_status ?? $existing->active_status,
                                'item_image' => $barcode->item_image ?? $existing->item_image,
                                'original_price' => $barcode->original_price ?? $existing->original_price,
                            ]);
                        }
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    if (!$dryRun) {
                        InventoryProduct::create([
                            'barcode_value' => $barcode->barcode_value,
                            'item_name' => $barcode->item_name,
                            'description' => $barcode->description,
                            'price' => $barcode->price,
                            'price_type' => $barcode->price_type,
                            'unit' => $this->normalizeUnit($barcode->unit ?? 'pcs'),
                            'quantity_on_hand' => \App\Support\ItemInventoryLinker::quantityForNewRow($barcode->quantity_on_hand),
                            'expiration_date' => $barcode->expiration_date,
                            'active_status' => $barcode->active_status ?? 'Active',
                            'item_image' => $barcode->item_image,
                            'original_price' => $barcode->original_price,
                        ]);
                    }
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error processing barcode '{$barcode->barcode_value}': " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return compact('imported', 'updated', 'skipped', 'errors');
    }

    private function importFromScannedProducts($update, $dryRun)
    {
        $scannedProducts = ScannedProduct::whereNotNull('item_name')
            ->whereNotNull('barcode_value')
            ->where('item_name', '!=', '')
            ->where('barcode_value', '!=', '')
            ->get();

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($scannedProducts->count());
        $progressBar->start();

        foreach ($scannedProducts as $scanned) {
            try {
                $existing = InventoryProduct::where('barcode_value', $scanned->barcode_value)->first();

                if ($existing) {
                    if ($update) {
                        if (!$dryRun) {
                            $existing->update([
                                'item_name' => $scanned->item_name,
                                'description' => $scanned->description ?? $existing->description,
                                'price_type' => $scanned->price_type ?? $existing->price_type,
                                'unit' => $scanned->unit ?? $existing->unit,
                                'expiration_date' => $scanned->expiration_date ?? $existing->expiration_date,
                                'active_status' => $scanned->active_status ?? $existing->active_status,
                                'item_image' => $scanned->item_image ?? $existing->item_image,
                            ]);
                        }
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    if (!$dryRun) {
                        InventoryProduct::create([
                            'barcode_value' => $scanned->barcode_value,
                            'item_name' => $scanned->item_name,
                            'description' => $scanned->description,
                            'price' => $scanned->price,
                            'price_type' => $scanned->price_type,
                            'unit' => $this->normalizeUnit($scanned->unit ?? 'pcs'),
                            'quantity_on_hand' => \App\Support\ItemInventoryLinker::quantityForNewRow($scanned->quantity_on_hand),
                            'expiration_date' => $scanned->expiration_date,
                            'active_status' => $scanned->active_status ?? 'Active',
                            'item_image' => $scanned->item_image ?? null,
                        ]);
                    }
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error processing scanned product '{$scanned->item_name}': " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return compact('imported', 'updated', 'skipped', 'errors');
    }

    private function importFromItemLists($update, $dryRun)
    {
        // Note: item_lists doesn't have barcode_value, so we'll need to match by item_name
        // or create products without barcode_value
        $itemLists = ItemList::whereNotNull('item')
            ->where('item', '!=', '')
            ->get();

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($itemLists->count());
        $progressBar->start();

        foreach ($itemLists as $item) {
            try {
                // Try to find by item_name first, then by barcode if available
                $existing = InventoryProduct::where('item_name', $item->item)->first();

                if ($existing) {
                    if ($update) {
                        if (!$dryRun) {
                            $existing->update([
                                'description' => $item->description ?? $existing->description,
                                'unit' => $item->unit_of_measure ?? $existing->unit,
                                'active_status' => $item->active_status ?? $existing->active_status,
                                'item_image' => $item->item_image ?? $existing->item_image,
                                'expiration_date' => $item->expiry_date ?? $existing->expiration_date,
                            ]);
                        }
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    // Generate a unique barcode_value if not exists
                    $barcodeValue = 'ITEM-' . str_pad($item->id, 8, '0', STR_PAD_LEFT);
                    
                    // Check if this barcode already exists
                    while (InventoryProduct::where('barcode_value', $barcodeValue)->exists()) {
                        $barcodeValue = 'ITEM-' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                    }

                    if (!$dryRun) {
                        InventoryProduct::create([
                            'barcode_value' => $barcodeValue,
                            'item_name' => $item->item,
                            'description' => $item->description,
                            'price' => $item->price,
                            'price_type' => null, // item_lists doesn't have price_type
                            'unit' => $this->normalizeUnit($item->unit_of_measure ?? 'pcs'),
                            'quantity_on_hand' => \App\Support\ItemInventoryLinker::quantityForNewRow($item->quantity_on_hand),
                            'expiration_date' => $item->expiry_date, // Use expiry_date from item_lists
                            'active_status' => $item->active_status ?? 'Active',
                            'item_image' => $item->item_image, // Include item_image
                        ]);
                    }
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error processing item '{$item->item}': " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return compact('imported', 'updated', 'skipped', 'errors');
    }

    /**
     * Normalize unit value to handle various formats
     */
    private function normalizeUnit($unit)
    {
        if (empty($unit)) {
            return 'pcs';
        }

        $unit = trim($unit);
        
        // If it's already a simple value like 'pcs', 'box', 'case', return as is
        if (in_array(strtolower($unit), ['pcs', 'box', 'case', 'pair', 'bottle', 'pack', 'liter', 'gallon'])) {
            return strtolower($unit);
        }

        // Extract unit from formats like "PIECE (PC)", "PAIR (PAIR)", etc.
        if (preg_match('/\(([^)]+)\)/', $unit, $matches)) {
            $extracted = strtolower(trim($matches[1]));
            $unitMap = [
                'pc' => 'pcs',
                'pair' => 'pair',
                'btl' => 'bottle',
                'pck' => 'pack',
                'box' => 'box',
                'ltr' => 'liter',
                'gal' => 'gallon',
            ];
            return $unitMap[$extracted] ?? $extracted;
        }

        // Map common variations
        $unitLower = strtolower($unit);
        $unitMap = [
            'piece (pc)' => 'pcs',
            'pieces' => 'pcs',
            'pc' => 'pcs',
            'pair (pair)' => 'pair',
            'pairs' => 'pair',
            'bottle (btl)' => 'bottle',
            'bottles' => 'bottle',
            'pack (pck)' => 'pack',
            'packs' => 'pack',
            'box (box)' => 'box',
            'boxes' => 'box',
            'liter (ltr)' => 'liter',
            'liters' => 'liter',
            'gallon (gal)' => 'gallon',
            'gallons' => 'gallon',
        ];

        return $unitMap[$unitLower] ?? strtolower($unit);
    }
}
