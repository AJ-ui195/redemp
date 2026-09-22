<?php

namespace Tests\Unit;

use App\Models\Barcode;
use Tests\TestCase;

class BarcodeOriginalPriceColumnTest extends TestCase
{
    public function test_keeps_original_price_when_column_exists(): void
    {
        $filtered = Barcode::attributesForExistingColumns(
            ['item_name' => 'Gauze', 'original_price' => 100],
            ['item_name', 'original_price', 'price']
        );

        $this->assertSame(100, $filtered['original_price']);
        $this->assertArrayNotHasKey('costing_price', $filtered);
    }

    public function test_maps_original_price_to_costing_price_when_that_is_the_column(): void
    {
        $filtered = Barcode::attributesForExistingColumns(
            ['item_name' => 'Gauze', 'original_price' => 100],
            ['item_name', 'costing_price', 'price']
        );

        $this->assertArrayNotHasKey('original_price', $filtered);
        $this->assertSame(100, $filtered['costing_price']);
    }

    public function test_drops_original_price_when_neither_column_exists(): void
    {
        $filtered = Barcode::attributesForExistingColumns(
            ['item_name' => 'Gauze', 'original_price' => 100, 'price' => 190],
            ['item_name', 'price', 'barcode_value']
        );

        $this->assertArrayNotHasKey('original_price', $filtered);
        $this->assertArrayNotHasKey('costing_price', $filtered);
        $this->assertSame(190, $filtered['price']);
    }
}
