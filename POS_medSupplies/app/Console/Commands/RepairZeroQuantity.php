<?php

namespace App\Console\Commands;

use App\Models\InventoryProduct;
use App\Models\ItemList;
use App\Support\ItemInventoryLinker;
use Illuminate\Console\Command;

class RepairZeroQuantity extends Command
{
    protected $signature = 'inventory:repair-zero-qty
                            {--dry-run : Show what would be repaired without writing}';

    protected $description = 'Restore inventory qty from a matched item_list only when inventory is 0 and the twin qty is positive';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN - no quantities will be changed');
        }

        $itemLists = ItemList::query()->get();
        $repaired = 0;
        $skipped = 0;

        foreach (InventoryProduct::query()->get() as $inventoryProduct) {
            $inventoryQty = (float) ($inventoryProduct->quantity_on_hand ?? 0);
            if ($inventoryQty > 0) {
                $skipped++;
                continue;
            }

            $itemList = ItemInventoryLinker::findMatchingItemListFromCollection($itemLists, $inventoryProduct);
            if (!$itemList) {
                $skipped++;
                continue;
            }

            $itemListQty = (float) ($itemList->quantity_on_hand ?? 0);
            if ($itemListQty <= 0) {
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                '%s [%s] inventory #%d qty 0 <- item_list #%d qty %s',
                $inventoryProduct->item_name,
                ItemInventoryLinker::normalizePriceType($inventoryProduct->price_type) ?? 'none',
                $inventoryProduct->id,
                $itemList->id,
                $itemListQty
            ));

            if (!$dryRun) {
                $inventoryProduct->update(['quantity_on_hand' => $itemListQty]);
            }

            $repaired++;
        }

        $this->newLine();
        $this->info("Repaired: {$repaired}");
        $this->info("Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
