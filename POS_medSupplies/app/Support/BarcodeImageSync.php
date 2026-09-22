<?php

namespace App\Support;

use App\Models\Barcode;
use App\Models\InventoryProduct;
use App\Models\ItemList;
use Illuminate\Support\Facades\DB;

class BarcodeImageSync
{
    /**
     * Copy barcode photo paths onto matching inventory_products and empty item_lists.
     * Does not create products or change qty/price.
     *
     * @return array{
     *     inventory_filled: int,
     *     item_list_filled: int,
     *     skipped_has_image: int,
     *     skipped_no_match: int,
     *     missing_files: int,
     *     changes: list<string>
     * }
     */
    public static function run(bool $dryRun): array
    {
        $barcodes = Barcode::query()
            ->whereNotNull('item_image')
            ->where('item_image', '!=', '')
            ->orderBy('id')
            ->get();

        $inventoryProducts = InventoryProduct::query()->get();
        $itemLists = ItemList::query()->get();

        $inventoryFilled = 0;
        $itemListFilled = 0;
        $skippedHasImage = 0;
        $skippedNoMatch = 0;
        $missingFiles = 0;
        $changes = [];

        foreach ($barcodes as $barcode) {
            $imagePath = str_replace('\\', '/', trim((string) $barcode->item_image));
            if ($imagePath === '') {
                continue;
            }

            if (!str_starts_with($imagePath, 'http') && !is_file(public_path($imagePath))) {
                $missingFiles++;
            }

            $inventory = ItemInventoryLinker::findMatchingInventoryProductForBarcode($inventoryProducts, $barcode);
            if ($inventory) {
                if (!ItemInventoryLinker::imagePathIsEmpty($inventory->item_image)) {
                    $skippedHasImage++;
                } else {
                    $changes[] = sprintf(
                        'inventory #%d %s [%s] <- %s',
                        $inventory->id,
                        $inventory->item_name,
                        ItemInventoryLinker::normalizePriceType($inventory->price_type) ?? 'none',
                        $imagePath
                    );

                    if (!$dryRun) {
                        DB::table('inventory_products')
                            ->where('id', $inventory->id)
                            ->update(['item_image' => $imagePath, 'updated_at' => now()]);
                        $inventory->item_image = $imagePath;
                    }

                    $inventoryFilled++;
                }
            } else {
                $skippedNoMatch++;
            }

            $itemList = ItemInventoryLinker::findMatchingItemListForBarcode($itemLists, $barcode);
            if (!$itemList || !ItemInventoryLinker::imagePathIsEmpty($itemList->item_image)) {
                continue;
            }

            $changes[] = sprintf(
                'item_list #%d %s [%s] <- %s',
                $itemList->id,
                $itemList->item,
                ItemInventoryLinker::normalizePriceType($itemList->price_type) ?? 'none',
                $imagePath
            );

            if (!$dryRun) {
                DB::table('item_lists')
                    ->where('id', $itemList->id)
                    ->update(['item_image' => $imagePath, 'updated_at' => now()]);
                $itemList->item_image = $imagePath;
            }

            $itemListFilled++;
        }

        return [
            'inventory_filled' => $inventoryFilled,
            'item_list_filled' => $itemListFilled,
            'skipped_has_image' => $skippedHasImage,
            'skipped_no_match' => $skippedNoMatch,
            'missing_files' => $missingFiles,
            'changes' => $changes,
        ];
    }
}
