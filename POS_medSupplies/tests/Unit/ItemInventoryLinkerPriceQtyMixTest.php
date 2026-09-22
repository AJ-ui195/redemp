<?php

namespace Tests\Unit;

use App\Models\InventoryProduct;
use App\Models\ItemList;
use App\Support\ItemInventoryLinker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ItemInventoryLinkerPriceQtyMixTest extends TestCase
{
    private const ITEM_NAME = 'TEST COTTON 400g';
    private const BARCODE = 'BC-COTTON-400';

    /**
     * Old matcher: empty price_type matched every type (the production bug).
     */
    private function oldPriceTypesMatch($left, $right): bool
    {
        $leftType = ItemInventoryLinker::normalizePriceType($left);
        $rightType = ItemInventoryLinker::normalizePriceType($right);

        if ($leftType === null || $rightType === null) {
            return true;
        }

        return $leftType === $rightType;
    }

    private function retailInventoryProduct(?string $priceType = 'retail'): InventoryProduct
    {
        return new InventoryProduct([
            'item_name' => self::ITEM_NAME,
            'barcode_value' => self::BARCODE,
            'price_type' => $priceType,
            'price' => 15,
            'quantity_on_hand' => 10,
        ]);
    }

    private function twins(): Collection
    {
        return collect([
            new ItemList([
                'item' => self::ITEM_NAME,
                'mpn' => self::BARCODE,
                'price_type' => 'retail',
                'price' => 15,
                'quantity_on_hand' => 10,
            ]),
            new ItemList([
                'item' => self::ITEM_NAME,
                'mpn' => self::BARCODE,
                'price_type' => 'wholesale',
                'price' => 8,
                'quantity_on_hand' => 1000,
            ]),
        ]);
    }

    public function test_old_matcher_would_pair_missing_price_type_with_wholesale(): void
    {
        $this->assertTrue($this->oldPriceTypesMatch(null, 'wholesale'));
        $this->assertTrue($this->oldPriceTypesMatch('', 'wholesale'));
    }

    public function test_new_matcher_does_not_pair_missing_price_type_with_wholesale(): void
    {
        $this->assertFalse(ItemInventoryLinker::priceTypesMatch(null, 'wholesale'));
        $this->assertFalse(ItemInventoryLinker::priceTypesMatch('', 'wholesale'));
        $this->assertFalse(ItemInventoryLinker::priceTypesMatch('retail', null));
    }

    public function test_same_price_types_still_match(): void
    {
        $this->assertTrue(ItemInventoryLinker::priceTypesMatch('retail', 'retail'));
        $this->assertTrue(ItemInventoryLinker::priceTypesMatch('wholesale', 'WHOLESALE'));
        $this->assertTrue(ItemInventoryLinker::priceTypesMatch(null, null));
        $this->assertFalse(ItemInventoryLinker::priceTypesMatch('retail', 'wholesale'));
    }

    public function test_retail_inventory_row_does_not_pick_wholesale_qty_1000_or_price_8(): void
    {
        $matched = ItemInventoryLinker::findMatchingItemListFromCollection(
            $this->twins(),
            $this->retailInventoryProduct('retail')
        );

        $this->assertNotNull($matched);
        $this->assertSame('retail', ItemInventoryLinker::normalizePriceType($matched->price_type));
        $this->assertEquals(15, (float) $matched->price);
        $this->assertEquals(10, (float) $matched->quantity_on_hand);
        $this->assertNotEquals(8, (float) $matched->price);
        $this->assertNotEquals(1000, (float) $matched->quantity_on_hand);
    }

    public function test_inventory_with_missing_price_type_does_not_grab_wholesale_twin(): void
    {
        $matched = ItemInventoryLinker::findMatchingItemListFromCollection(
            $this->twins(),
            $this->retailInventoryProduct(null)
        );

        $this->assertNull(
            $matched,
            'Missing price_type must not pair with wholesale 8 / 1000 when a retail twin also exists'
        );
    }

    public function test_wholesale_row_still_matches_wholesale_only(): void
    {
        $wholesaleInv = new InventoryProduct([
            'item_name' => self::ITEM_NAME,
            'barcode_value' => self::BARCODE,
            'price_type' => 'wholesale',
            'price' => 8,
            'quantity_on_hand' => 1000,
        ]);

        $matched = ItemInventoryLinker::findMatchingItemListFromCollection($this->twins(), $wholesaleInv);

        $this->assertNotNull($matched);
        $this->assertSame('wholesale', ItemInventoryLinker::normalizePriceType($matched->price_type));
        $this->assertEquals(8, (float) $matched->price);
        $this->assertEquals(1000, (float) $matched->quantity_on_hand);
    }

    public function test_observer_would_not_copy_qty_unless_stock_actually_changed(): void
    {
        $itemList = new ItemList([
            'item' => self::ITEM_NAME,
            'price_type' => 'retail',
            'quantity_on_hand' => 10,
            'price' => 15,
        ]);
        $itemList->syncOriginal();

        $itemList->setAttribute('price', '15');
        $itemList->item = self::ITEM_NAME;

        $this->assertFalse(
            $itemList->isDirty('quantity_on_hand'),
            'Name/price-only save must not look like a stock change'
        );

        $itemList->setAttribute('quantity_on_hand', '1000');
        $this->assertTrue($itemList->isDirty('quantity_on_hand'));
    }

    public function test_displayed_quantity_prefers_inventory_product_stock(): void
    {
        $itemList = new ItemList([
            'item' => self::ITEM_NAME,
            'price_type' => 'retail',
            'quantity_on_hand' => 0,
        ]);
        $inventory = $this->retailInventoryProduct('retail');
        $inventory->quantity_on_hand = 50;

        $this->assertSame(50.0, ItemInventoryLinker::displayedQuantity($itemList, $inventory));
        $this->assertSame(0.0, ItemInventoryLinker::displayedQuantity($itemList, null));
    }

    public function test_names_match_ignores_case_and_whitespace(): void
    {
        $this->assertTrue(ItemInventoryLinker::namesMatch('Syringe 5ml', ' syringe 5ML '));
        $this->assertFalse(ItemInventoryLinker::namesMatch('Syringe 5ml', 'Syringe 10ml'));
    }

    public function test_cashier_collection_match_uses_inventory_qty_not_zero_item_list(): void
    {
        $itemList = new ItemList([
            'item' => self::ITEM_NAME,
            'mpn' => self::BARCODE,
            'price_type' => 'retail',
            'quantity_on_hand' => 0,
        ]);
        $inventory = $this->retailInventoryProduct('retail');
        $inventory->quantity_on_hand = 42;

        $matched = ItemInventoryLinker::findMatchingInventoryProductFromCollection(
            collect([$inventory]),
            $itemList
        );

        $this->assertNotNull($matched);
        $this->assertSame(42.0, ItemInventoryLinker::displayedQuantity($itemList, $matched));
    }

    public function test_generated_barcode_pairs_inventory_row_to_item_list(): void
    {
        $itemList = new ItemList([
            'item' => 'Cashier Only Name',
            'mpn' => 'OTHER-MPN',
            'price_type' => 'retail',
            'quantity_on_hand' => 0,
        ]);
        $itemList->id = 42;

        $inventory = new InventoryProduct([
            'item_name' => 'Inventory Display Name',
            'barcode_value' => ItemInventoryLinker::generatedBarcode(42),
            'price_type' => 'retail',
            'quantity_on_hand' => 12,
        ]);

        $matched = ItemInventoryLinker::findMatchingItemListFromCollection(
            collect([$itemList]),
            $inventory
        );

        $this->assertNotNull($matched);
        $this->assertSame(42, (int) $matched->id);
    }

    public function test_null_qty_for_new_row_does_not_invent_stock(): void
    {
        $this->assertSame(0.0, ItemInventoryLinker::quantityForNewRow(null));
        $this->assertSame(0.0, ItemInventoryLinker::quantityForNewRow(''));
        $this->assertSame(8.0, ItemInventoryLinker::quantityForNewRow(8));
    }

    public function test_displayed_quantity_uses_inventory_not_item_list(): void
    {
        $itemList = new ItemList([
            'item' => self::ITEM_NAME,
            'quantity_on_hand' => 1000,
        ]);
        $inventory = new InventoryProduct([
            'item_name' => self::ITEM_NAME,
            'quantity_on_hand' => 4,
        ]);

        $this->assertSame(4.0, ItemInventoryLinker::displayedQuantity($itemList, $inventory));
        $this->assertSame(1000.0, ItemInventoryLinker::displayedQuantity($itemList, null));
    }

    public function test_barcode_image_matches_same_barcode_and_price_type_only(): void
    {
        $inventory = collect([
            $this->retailInventoryProduct('retail'),
            new InventoryProduct([
                'item_name' => self::ITEM_NAME,
                'barcode_value' => self::BARCODE,
                'price_type' => 'wholesale',
                'item_image' => null,
            ]),
        ]);
        $inventory[0]->id = 11;
        $inventory[1]->id = 12;
        $inventory[0]->item_image = null;

        $retailBarcode = new \App\Models\Barcode([
            'barcode_value' => self::BARCODE,
            'item_name' => self::ITEM_NAME,
            'price_type' => 'retail',
            'item_image' => 'images/barcodes/retail.png',
        ]);

        $matched = ItemInventoryLinker::findMatchingInventoryProductForBarcode($inventory, $retailBarcode);

        $this->assertNotNull($matched);
        $this->assertSame(11, (int) $matched->id);
    }

    public function test_barcode_image_does_not_match_other_price_type(): void
    {
        $inventory = collect([
            new InventoryProduct([
                'item_name' => self::ITEM_NAME,
                'barcode_value' => 'OTHER-BC',
                'price_type' => 'wholesale',
                'item_image' => null,
            ]),
        ]);
        $inventory[0]->id = 12;

        $retailBarcode = new \App\Models\Barcode([
            'barcode_value' => self::BARCODE,
            'item_name' => self::ITEM_NAME,
            'price_type' => 'retail',
            'item_image' => 'images/barcodes/retail.png',
        ]);

        $this->assertNull(ItemInventoryLinker::findMatchingInventoryProductForBarcode($inventory, $retailBarcode));
    }
}
