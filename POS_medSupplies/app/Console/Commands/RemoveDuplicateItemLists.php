<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ItemList;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateItemLists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'item-lists:remove-duplicates 
                            {--dry-run : Show what would be removed without actually removing}
                            {--merge-quantities : Merge quantities from duplicates into the kept entry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate entries from item_lists table (case-insensitive name matching)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $mergeQuantities = $this->option('merge-quantities');

        $this->info('Finding and removing duplicate entries from item_lists...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get all item_lists
        $allItems = ItemList::orderBy('id')->get();
        
        // Group items by lowercase name
        $itemsByName = [];
        foreach ($allItems as $item) {
            $itemName = trim($item->item);
            
            if (empty($itemName)) {
                continue;
            }
            
            $nameLower = strtolower($itemName);
            
            if (!isset($itemsByName[$nameLower])) {
                $itemsByName[$nameLower] = [];
            }
            
            $itemsByName[$nameLower][] = $item;
        }

        $totalDuplicates = 0;
        $totalRemoved = 0;
        $totalMerged = 0;
        $duplicatesToRemove = [];
        $itemsToUpdate = [];

        // Find duplicates
        foreach ($itemsByName as $nameLower => $items) {
            if (count($items) > 1) {
                $totalDuplicates += count($items) - 1; // All except one are duplicates
                
                // Sort items to determine which one to keep
                // Prefer items with more complete data (non-null fields)
                usort($items, function($a, $b) {
                    $scoreA = $this->calculateCompletenessScore($a);
                    $scoreB = $this->calculateCompletenessScore($b);
                    
                    if ($scoreA !== $scoreB) {
                        return $scoreB <=> $scoreA; // Higher score first
                    }
                    
                    // If scores are equal, prefer the one with higher quantity
                    $qtyA = (float) ($a->quantity_on_hand ?? 0);
                    $qtyB = (float) ($b->quantity_on_hand ?? 0);
                    
                    if ($qtyA !== $qtyB) {
                        return $qtyB <=> $qtyA; // Higher quantity first
                    }
                    
                    // If still equal, prefer the one with lower ID (older entry)
                    return $a->id <=> $b->id;
                });
                
                // Keep the first one (best entry)
                $keepItem = $items[0];
                $duplicateItems = array_slice($items, 1);
                
                // Calculate total quantity if merging
                $totalQuantity = (float) ($keepItem->quantity_on_hand ?? 0);
                if ($mergeQuantities) {
                    foreach ($duplicateItems as $dup) {
                        $totalQuantity += (float) ($dup->quantity_on_hand ?? 0);
                    }
                }
                
                // Mark duplicates for removal
                foreach ($duplicateItems as $dup) {
                    $duplicatesToRemove[] = [
                        'id' => $dup->id,
                        'name' => $dup->item,
                        'quantity' => $dup->quantity_on_hand ?? 0,
                        'keep_id' => $keepItem->id,
                        'keep_name' => $keepItem->item,
                    ];
                }
                
                // If merging quantities, update the kept item
                if ($mergeQuantities && $totalQuantity != (float) ($keepItem->quantity_on_hand ?? 0)) {
                    $itemsToUpdate[] = [
                        'id' => $keepItem->id,
                        'name' => $keepItem->item,
                        'old_quantity' => $keepItem->quantity_on_hand ?? 0,
                        'new_quantity' => $totalQuantity,
                    ];
                }
            }
        }

        if (count($duplicatesToRemove) === 0) {
            $this->info('No duplicate entries found in item_lists.');
            return 0;
        }

        // Show what will be removed
        $this->warn("Found {$totalDuplicates} duplicate entries to remove:");
        $this->newLine();
        
        // Group by item name for better display
        $groupedDuplicates = [];
        foreach ($duplicatesToRemove as $dup) {
            $nameLower = strtolower($dup['name']);
            if (!isset($groupedDuplicates[$nameLower])) {
                $groupedDuplicates[$nameLower] = [
                    'name' => $dup['name'],
                    'keep_id' => $dup['keep_id'],
                    'keep_name' => $dup['keep_name'],
                    'duplicates' => [],
                ];
            }
            $groupedDuplicates[$nameLower]['duplicates'][] = $dup;
        }

        foreach ($groupedDuplicates as $group) {
            $this->line("Item: '{$group['name']}'");
            $this->line("  Keeping: ID {$group['keep_id']} - '{$group['keep_name']}'");
            $this->line("  Removing " . count($group['duplicates']) . " duplicate(s):");
            
            foreach ($group['duplicates'] as $dup) {
                $this->line("    - ID {$dup['id']} (Quantity: {$dup['quantity']})");
            }
            $this->newLine();
        }

        if ($mergeQuantities && count($itemsToUpdate) > 0) {
            $this->info("Items to update with merged quantities:");
            $this->table(
                ['ID', 'Name', 'Old Quantity', 'New Quantity'],
                array_map(function($item) {
                    return [
                        $item['id'],
                        $item['name'],
                        $item['old_quantity'],
                        $item['new_quantity'],
                    ];
                }, $itemsToUpdate)
            );
            $this->newLine();
        }

        if (!$dryRun) {
            // Confirm before proceeding
            if (!$this->confirm('Do you want to proceed with removing these duplicates?', true)) {
                $this->info('Operation cancelled.');
                return 0;
            }

            $this->newLine();
            $this->info('Removing duplicates...');

            $progressBar = $this->output->createProgressBar(count($duplicatesToRemove));
            $progressBar->start();

            // Update quantities first if merging
            if ($mergeQuantities) {
                foreach ($itemsToUpdate as $update) {
                    ItemList::where('id', $update['id'])->update([
                        'quantity_on_hand' => $update['new_quantity']
                    ]);
                    $totalMerged++;
                }
            }

            // Remove duplicates
            foreach ($duplicatesToRemove as $dup) {
                try {
                    ItemList::where('id', $dup['id'])->delete();
                    $totalRemoved++;
                } catch (\Exception $e) {
                    $this->error("Error removing ID {$dup['id']}: " . $e->getMessage());
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);
        }

        // Summary
        $this->info('Summary:');
        $summary = [
            ['Total duplicates found', $totalDuplicates],
        ];
        
        if (!$dryRun) {
            $summary[] = ['Duplicates removed', $totalRemoved];
            if ($mergeQuantities) {
                $summary[] = ['Items updated with merged quantities', $totalMerged];
            }
        }
        
        $this->table(
            ['Action', 'Count'],
            $summary
        );

        if ($dryRun) {
            $this->warn('This was a dry run. No changes were made.');
            $this->info('Run the command without --dry-run to actually remove duplicates.');
        } else {
            $this->info('Duplicate removal completed successfully!');
        }

        return 0;
    }

    /**
     * Calculate a completeness score for an item (higher = more complete)
     */
    private function calculateCompletenessScore(ItemList $item): int
    {
        $score = 0;
        
        // Give points for having data in important fields
        if (!empty($item->description)) $score += 2;
        if (!empty($item->brand)) $score += 2;
        if (!empty($item->item_image)) $score += 3;
        if (!empty($item->mpn)) $score += 1;
        if (!empty($item->price)) $score += 1;
        if (!empty($item->lot_number)) $score += 1;
        if (!empty($item->expiry_date)) $score += 1;
        if (!empty($item->mfg_date)) $score += 1;
        if (!empty($item->active_status)) $score += 1;
        
        return $score;
    }
}
