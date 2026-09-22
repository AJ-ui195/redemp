<?php

namespace Tests\Unit;

use App\ItemImageAssetUrl;
use Tests\TestCase;

class ItemImageAssetUrlTest extends TestCase
{
    public function test_empty_path_returns_null(): void
    {
        $this->assertNull(ItemImageAssetUrl::resolve(null));
        $this->assertNull(ItemImageAssetUrl::resolve(''));
        $this->assertNull(ItemImageAssetUrl::resolve('   '));
    }

    public function test_barcode_path_uses_public_images_url(): void
    {
        $url = ItemImageAssetUrl::resolve('images/barcodes/barcode_1766417180_6949631c7e157.jpeg');

        $this->assertNotNull($url);
        $this->assertStringContainsString('/public/images/barcodes/barcode_1766417180_6949631c7e157.jpeg', $url);
        $this->assertStringNotContainsString('/public/public/images/', $url);
    }

    public function test_missing_barcode_file_still_uses_public_images_path(): void
    {
        $url = ItemImageAssetUrl::resolve('images/barcodes/does-not-exist.png');

        $this->assertNotNull($url);
        $this->assertStringContainsString('/public/images/barcodes/does-not-exist.png', $url);
        $this->assertStringNotContainsString('/public/public/images/', $url);
    }

    public function test_public_prefixed_path_does_not_double_public(): void
    {
        $url = ItemImageAssetUrl::resolve('public/images/barcodes/missing.png');

        $this->assertNotNull($url);
        $this->assertStringContainsString('/public/images/barcodes/missing.png', $url);
        $this->assertStringNotContainsString('/public/public/images/', $url);
    }

    public function test_absolute_url_keeps_public_images_segment(): void
    {
        $url = ItemImageAssetUrl::resolve('https://example.com/public/images/barcodes/a.png');

        $this->assertSame('https://example.com/public/images/barcodes/a.png', $url);
    }

    public function test_absolute_url_collapses_double_public_segment(): void
    {
        $url = ItemImageAssetUrl::resolve('https://example.com/public/public/images/barcodes/a.png');

        $this->assertSame('https://example.com/public/images/barcodes/a.png', $url);
    }
}
