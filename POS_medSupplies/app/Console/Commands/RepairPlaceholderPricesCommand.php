<?php

namespace App\Console\Commands;

use App\Support\RepairPlaceholderPrices;
use Illuminate\Console\Command;

class RepairPlaceholderPricesCommand extends Command
{
    protected $signature = 'inventory:repair-one-peso-prices';

    protected $description = 'Restore selling price when it is stuck at 1.0000 and a matched twin still has the real price';

    public function handle(): int
    {
        $result = RepairPlaceholderPrices::run();

        $this->info('Inventory prices restored: ' . $result['inventory']);
        $this->info('Item-list prices restored: ' . $result['item_lists']);
        $this->info('Barcode prices restored: ' . $result['barcodes']);

        return self::SUCCESS;
    }
}
