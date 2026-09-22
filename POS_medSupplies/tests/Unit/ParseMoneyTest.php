<?php

namespace Tests\Unit;

use App\Support\ItemInventoryLinker;
use Tests\TestCase;

class ParseMoneyTest extends TestCase
{
    public function test_comma_thousands_does_not_become_one(): void
    {
        $this->assertSame(1250.0, ItemInventoryLinker::parseMoney('1,250'));
        $this->assertSame(1250.5, ItemInventoryLinker::parseMoney('1,250.50'));
        $this->assertSame(1250.0, ItemInventoryLinker::parseMoney('₱1,250'));
    }

    public function test_placeholder_price_detection(): void
    {
        $this->assertTrue(ItemInventoryLinker::isPlaceholderPrice(1));
        $this->assertTrue(ItemInventoryLinker::isPlaceholderPrice('1.0000'));
        $this->assertFalse(ItemInventoryLinker::isPlaceholderPrice(1.25));
        $this->assertFalse(ItemInventoryLinker::isPlaceholderPrice(7312));
    }

    public function test_pick_selling_price_skips_placeholder(): void
    {
        $this->assertSame(7312.0, ItemInventoryLinker::pickSellingPrice([1, 7312]));
        $this->assertSame(1.0, ItemInventoryLinker::pickSellingPrice([1, '1.0000']));
        $this->assertSame(55.0, ItemInventoryLinker::pickSellingPrice([1, null, 55]));
    }

    public function test_trailing_period_names_still_match(): void
    {
        $this->assertTrue(ItemInventoryLinker::namesMatch(
            'ABSORBENT GAUZE ROLL SIZE 36X100 MESH 24X28.',
            'ABSORBENT GAUZE ROLL SIZE 36X100 MESH 24X28'
        ));
    }

    public function test_similar_names_match_typos_but_not_adult_pedia(): void
    {
        $this->assertTrue(ItemInventoryLinker::namesSimilarForPrice('NEBULIZE 407C', 'NEBULIZER 407C'));
        $this->assertFalse(ItemInventoryLinker::namesSimilarForPrice(
            'BP CUFF WITHOUT D-RING (ADULT) 540x 145MM',
            'BP CUFF WITH INFLATION BAG (ADULT)'
        ));
        $this->assertFalse(ItemInventoryLinker::namesSimilarForPrice(
            'OXYGEN MASK (ADULT)',
            'OXYGEN MASK (PEDIA)'
        ));
    }
}
