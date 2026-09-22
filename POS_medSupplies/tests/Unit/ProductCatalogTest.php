<?php

namespace Tests\Unit;

use App\Models\Barcode;
use App\Models\InventoryProduct;
use App\Models\ItemList;
use App\Support\ProductCatalog;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    private function itemList(int $id, array $attrs): ItemList
    {
        $item = new ItemList($attrs);
        $item->id = $id;

        return $item;
    }

    private function inventoryProduct(int $id, array $attrs): InventoryProduct
    {
        $product = new InventoryProduct($attrs);
        $product->id = $id;

        return $product;
    }

    private function barcode(int $id, array $attrs): Barcode
    {
        $barcode = new Barcode($attrs);
        $barcode->id = $id;

        return $barcode;
    }

    public function test_merge_keeps_retail_and_wholesale_separate(): void
    {
        $rows = ProductCatalog::merge(
            collect([
                $this->itemList(1, [
                    'item' => 'COTTON 400g',
                    'mpn' => 'BC-COTTON',
                    'price_type' => 'retail',
                    'quantity_on_hand' => 10,
                    'price' => 15,
                ]),
                $this->itemList(2, [
                    'item' => 'COTTON 400g',
                    'mpn' => 'BC-COTTON',
                    'price_type' => 'wholesale',
                    'quantity_on_hand' => 1000,
                    'price' => 8,
                ]),
            ]),
            collect([
                $this->inventoryProduct(11, [
                    'item_name' => 'COTTON 400g',
                    'barcode_value' => 'BC-COTTON',
                    'price_type' => 'retail',
                    'quantity_on_hand' => 10,
                    'price' => 15,
                ]),
                $this->inventoryProduct(12, [
                    'item_name' => 'COTTON 400g',
                    'barcode_value' => 'BC-COTTON',
                    'price_type' => 'wholesale',
                    'quantity_on_hand' => 1000,
                    'price' => 8,
                ]),
            ]),
            collect()
        );

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(
            ['retail', 'wholesale'],
            $rows->pluck('price_type')->all()
        );
        $this->assertEqualsCanonicalizing(
            [10.0, 1000.0],
            $rows->pluck('quantity_on_hand')->map(fn ($qty) => (float) $qty)->all()
        );
    }

    public function test_merge_does_not_show_duplicate_barcode_rows(): void
    {
        $rows = ProductCatalog::merge(
            collect([
                $this->itemList(1, [
                    'item' => 'ALCOHOL PREP PAD',
                    'mpn' => 'BC-ALCOHOL',
                    'price_type' => 'retail',
                    'quantity_on_hand' => 4,
                ]),
            ]),
            collect([
                $this->inventoryProduct(11, [
                    'item_name' => 'ALCOHOL PREP PAD',
                    'barcode_value' => 'BC-ALCOHOL',
                    'price_type' => 'retail',
                    'quantity_on_hand' => 4,
                ]),
            ]),
            collect([
                $this->barcode(21, [
                    'item_name' => 'ALCOHOL PREP PAD',
                    'barcode_value' => 'BC-ALCOHOL',
                    'price_type' => 'retail',
                    'quantity_on_hand' => 4,
                ]),
            ])
        );

        $this->assertCount(1, $rows);
        $this->assertSame('inventory_products', $rows->first()->source);
        $this->assertSame(4.0, (float) $rows->first()->quantity_on_hand);
    }

    public function test_inventory_row_uses_barcode_image_when_inventory_has_none(): void
    {
        $rows = ProductCatalog::merge(
            collect(),
            collect([
                $this->inventoryProduct(11, [
                    'item_name' => 'ABSORBENT COTTON 400g',
                    'barcode_value' => 'BC-COTTON',
                    'price_type' => 'retail',
                    'quantity_on_hand' => 10,
                    'item_image' => null,
                ]),
            ]),
            collect([
                $this->barcode(21, [
                    'item_name' => 'ABSORBENT COTTON 400g',
                    'barcode_value' => 'BC-COTTON',
                    'price_type' => 'retail',
                    'item_image' => 'images/barcodes/cotton.jpg',
                ]),
            ])
        );

        $this->assertCount(1, $rows);
        $this->assertSame('images/barcodes/cotton.jpg', $rows->first()->item_image);
    }

    public function test_inventory_uses_barcode_image_when_barcode_numbers_differ(): void
    {
        $rows = ProductCatalog::merge(
            collect(),
            collect([
                $this->inventoryProduct(11, [
                    'item_name' => 'BLADE HOLDER # 3',
                    'barcode_value' => 'ITEM-00000011',
                    'price_type' => 'retail',
                    'quantity_on_hand' => 2,
                    'item_image' => null,
                ]),
            ]),
            collect([
                $this->barcode(21, [
                    'item_name' => 'BLADE HOLDER # 3',
                    'barcode_value' => 'BC-BLADE-3',
                    'price_type' => 'retail',
                    'item_image' => 'images/barcodes/blade.png',
                ]),
            ])
        );

        $this->assertCount(1, $rows);
        $this->assertSame('images/barcodes/blade.png', $rows->first()->item_image);
    }

    public function test_retail_does_not_take_wholesale_barcode_image_by_name(): void
    {
        $rows = ProductCatalog::merge(
            collect(),
            collect([
                $this->inventoryProduct(11, [
                    'item_name' => 'ABSORBENT COTTON 400g',
                    'barcode_value' => 'ITEM-00000011',
                    'price_type' => 'retail',
                    'quantity_on_hand' => 10,
                    'item_image' => null,
                ]),
            ]),
            collect([
                $this->barcode(21, [
                    'item_name' => 'ABSORBENT COTTON 400g',
                    'barcode_value' => 'BC-COTTON-W',
                    'price_type' => 'wholesale',
                    'item_image' => 'images/barcodes/wholesale.jpg',
                ]),
            ])
        );

        $this->assertCount(2, $rows);
        $retail = $rows->first(fn ($row) => $row->price_type === 'retail');
        $this->assertNotNull($retail);
        $this->assertNull($retail->item_image);
    }

    public function test_inventory_row_uses_item_list_image_when_inventory_has_none(): void
    {
        $rows = ProductCatalog::merge(
            collect([
                $this->itemList(1, [
                    'item' => 'ABSORBENT COTTON 400g',
                    'mpn' => 'BC-COTTON',
                    'price_type' => 'retail',
                    'item_image' => 'images/inventory/cotton.jpg',
                ]),
            ]),
            collect([
                $this->inventoryProduct(11, [
                    'item_name' => 'ABSORBENT COTTON 400g',
                    'barcode_value' => 'BC-COTTON',
                    'price_type' => 'retail',
                    'quantity_on_hand' => 10,
                    'item_image' => '',
                ]),
            ]),
            collect()
        );

        $this->assertCount(1, $rows);
        $this->assertSame('images/inventory/cotton.jpg', $rows->first()->item_image);
    }

    public function test_merge_includes_leftover_barcodes(): void
    {
        $rows = ProductCatalog::merge(
            collect(),
            collect(),
            collect([
                $this->barcode(21, [
                    'item_name' => 'GLOVES LATEX',
                    'barcode_value' => 'BC-GLOVES',
                    'price_type' => 'retail',
                    'quantity_on_hand' => 12,
                ]),
            ])
        );

        $this->assertCount(1, $rows);
        $this->assertSame('barcodes', $rows->first()->source);
        $this->assertSame('GLOVES LATEX', $rows->first()->item_name);
        $this->assertSame('bc-21', $rows->first()->cashier_key);
    }

    public function test_apostrophe_search_matches_item_name(): void
    {
        $row = (object) [
            'item_name' => "ALCOHOL PADS 100'S/BOX",
            'brand' => null,
            'description' => null,
            'lot_number' => null,
            'barcode' => 'BC-PADS',
        ];

        $this->assertTrue(ProductCatalog::matchesSearch($row, '100s/BOX'));
        $this->assertTrue(ProductCatalog::matchesSearch($row, "100's/box"));
        $this->assertFalse(ProductCatalog::matchesSearch($row, 'gloves'));
    }

    public function test_blank_name_falls_back_to_unnamed_item(): void
    {
        $rows = ProductCatalog::merge(
            collect(),
            collect([
                $this->inventoryProduct(99, [
                    'item_name' => '',
                    'barcode_value' => 'BC-EMPTY',
                    'price_type' => 'retail',
                    'quantity_on_hand' => 1,
                ]),
            ]),
            collect()
        );

        $this->assertCount(1, $rows);
        $this->assertSame('Unnamed item #99', $rows->first()->item_name);
    }

    public function test_inventory_placeholder_price_uses_item_list_price(): void
    {
        $rows = ProductCatalog::merge(
            collect([
                $this->itemList(1, [
                    'item' => 'MALARIA P.F/P.V 40s TESTS',
                    'mpn' => 'ONE STEP MALARIA',
                    'price_type' => 'retail',
                    'price' => 7312,
                ]),
            ]),
            collect([
                $this->inventoryProduct(11, [
                    'item_name' => 'MALARIA P.F/P.V 40s TESTS',
                    'barcode_value' => 'ONE STEP MALARIA',
                    'price_type' => 'retail',
                    'price' => 1,
                ]),
            ]),
            collect()
        );

        $this->assertCount(1, $rows);
        $this->assertSame(7312.0, (float) $rows->first()->price);
    }
}
