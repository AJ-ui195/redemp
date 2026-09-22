<?php

namespace App;

final class ItemImageAssetUrl
{
    public static function resolve(?string $stored): ?string
    {
        if ($stored === null || trim($stored) === '') {
            return null;
        }

        $p = str_replace('\\', '/', trim($stored));

        if (! str_starts_with($p, 'http://') && ! str_starts_with($p, 'https://')) {
            $p = ltrim($p, '/');
        }

        if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
            return self::withoutExtraPublicSegment($p);
        }

        $found = self::existingRelative($p);
        if ($found !== null) {
            return self::toAsset($found);
        }

        if (str_starts_with($p, 'storage/')) {
            return self::toAsset($p);
        }

        if (str_starts_with($p, 'public/')) {
            return self::toAsset($p);
        }

        if (str_starts_with($p, 'images/')) {
            return self::toAsset($p);
        }

        $basename = basename($p);

        if (str_starts_with($p, 'products/')) {
            return self::toAsset('images/products/'.$basename);
        }

        if (str_starts_with($basename, 'barcode_')
            || str_starts_with($basename, 'itemlist_')
            || str_starts_with($basename, 'inventory_')
            || str_starts_with($basename, 'product_')) {
            return self::toAsset('images/products/'.$basename);
        }

        return self::toAsset('images/products/'.$basename);
    }

    private static function toAsset(string $relative): string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        if (str_starts_with($relative, 'public/')) {
            $relative = substr($relative, 7);
        }

        return self::webPath($relative);
    }

    /**
     * Build a browser URL for a file under Laravel's public/ directory.
     * - php artisan serve / vhost pointing at public/ → /images/...
     * - Laragon/Hostinger project web root → /public/images/...
     */
    private static function webPath(string $relative): string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        $base = self::requestBasePath();
        $prefix = self::documentRootIsPublic() ? '' : '/public';

        return $base.$prefix.'/'.$relative;
    }

    private static function documentRootIsPublic(): bool
    {
        try {
            $docRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
            $public = realpath(public_path());

            if ($docRoot && $public && $docRoot === $public) {
                return true;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        $software = (string) ($_SERVER['SERVER_SOFTWARE'] ?? '');

        return str_contains($software, 'Development Server');
    }

    private static function requestBasePath(): string
    {
        try {
            $base = rtrim(str_replace('\\', '/', (string) request()->getBasePath()), '/');
            if ($base === '.' || $base === '\\') {
                return '';
            }

            return $base;
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function withoutExtraPublicSegment(string $url): string
    {
        return preg_replace('#/public/public/(images|storage)/#', '/public/$1/', $url) ?? $url;
    }

    private static function existingRelative(string $p): ?string
    {
        $candidates = [];

        if (str_starts_with($p, 'public/')) {
            $candidates[] = $p;
            $candidates[] = substr($p, 7);
        } elseif (str_starts_with($p, 'storage/')) {
            $candidates[] = $p;
        } elseif (str_starts_with($p, 'images/')) {
            $candidates[] = $p;
            $candidates[] = 'public/'.$p;
        } elseif (str_starts_with($p, 'products/')) {
            $candidates[] = 'images/products/'.basename($p);
            $candidates[] = 'storage/'.$p;
            $candidates[] = $p;
        } else {
            $candidates[] = 'images/products/'.$p;
            $candidates[] = 'images/inventory/'.$p;
            $candidates[] = 'images/barcodes/'.$p;
            $candidates[] = 'images/'.$p;
        }

        $basename = basename($p);
        if ($basename !== '') {
            $candidates[] = 'images/products/'.$basename;
            $candidates[] = 'images/barcodes/'.$basename;
            $candidates[] = 'images/inventory/'.$basename;
            $candidates[] = 'images/'.$basename;
            $candidates[] = 'storage/products/'.$basename;
        }

        foreach ($candidates as $relative) {
            $found = self::fileIfExists($relative);
            if ($found !== null) {
                return $found;
            }
        }

        return self::caseInsensitiveIn('images/products', $basename)
            ?? self::caseInsensitiveIn('images/barcodes', $basename)
            ?? self::caseInsensitiveIn('images/inventory', $basename);
    }

    private static function fileIfExists(string $relative): ?string
    {
        $relative = ltrim($relative, '/');

        if (str_starts_with($relative, 'storage/')) {
            $full = storage_path('app/public/'.substr($relative, 8));

            return is_file($full) ? $relative : null;
        }

        if (str_starts_with($relative, 'public/')) {
            if (is_file(base_path($relative))) {
                return substr($relative, 7);
            }
            $underPublic = substr($relative, 7);

            return is_file(public_path($underPublic)) ? $underPublic : null;
        }

        if (is_file(public_path($relative))) {
            return $relative;
        }

        if (is_file(base_path('public/'.$relative))) {
            return $relative;
        }

        if (is_file(base_path($relative)) && str_starts_with($relative, 'images/')) {
            return $relative;
        }

        return null;
    }

    private static function caseInsensitiveIn(string $dirUnderPublic, string $basename): ?string
    {
        if ($basename === '') {
            return null;
        }

        $relative = $dirUnderPublic.'/'.$basename;
        if (is_file(public_path($relative))) {
            return $relative;
        }

        $dir = public_path($dirUnderPublic);
        if (! is_dir($dir)) {
            return null;
        }

        foreach (@scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            if (strcasecmp($f, $basename) === 0) {
                return $dirUnderPublic.'/'.$f;
            }
        }

        return null;
    }
}
