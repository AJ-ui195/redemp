<?php

namespace App\Support;

/**
 * All product photos are stored under a single public folder.
 */
final class ProductImageStorage
{
    public const DIRECTORY = 'images/products';

    public static function directory(): string
    {
        return self::DIRECTORY;
    }

    public static function absoluteDirectory(): string
    {
        $dir = public_path(self::DIRECTORY);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public static function storeUploadedFile($file, string $prefix = 'product'): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === '' || $extension === 'jpeg') {
            $extension = 'jpg';
        }

        $filename = $prefix.'_'.time().'_'.uniqid().'.'.$extension;
        $file->move(self::absoluteDirectory(), $filename);

        return self::assertSaved(self::DIRECTORY.'/'.$filename);
    }

    public static function storeBinary(string $binary, string $extension, string $prefix = 'product'): string
    {
        $extension = strtolower($extension);
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $filename = $prefix.'_'.time().'_'.uniqid().'.'.$extension;
        $relative = self::DIRECTORY.'/'.$filename;
        $bytes = file_put_contents(public_path($relative), $binary);

        if ($bytes === false) {
            throw new \RuntimeException('Failed to save image file.');
        }

        return self::assertSaved($relative);
    }

    public static function delete(?string $relativePath): void
    {
        $relativePath = str_replace('\\', '/', trim((string) $relativePath));
        if ($relativePath === '') {
            return;
        }

        if (str_starts_with($relativePath, 'images/')) {
            $full = public_path($relativePath);
            if (is_file($full)) {
                @unlink($full);
            }

            return;
        }

        if (str_starts_with($relativePath, 'storage/')) {
            $relativePath = substr($relativePath, 8);
        }

        if (str_starts_with($relativePath, 'products/')) {
            $full = storage_path('app/public/'.$relativePath);
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    private static function assertSaved(string $relative): string
    {
        $saved = public_path($relative);
        if (! is_file($saved) || ! is_readable($saved) || filesize($saved) === 0) {
            throw new \RuntimeException('Failed to save image file.');
        }

        return $relative;
    }
}
