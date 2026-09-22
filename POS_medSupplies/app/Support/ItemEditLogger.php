<?php

namespace App\Support;

use App\Models\ItemEditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ItemEditLogger
{
    private const TRACKED_FIELDS = [
        'item',
        'item_name',
        'brand',
        'description',
        'price',
        'original_price',
        'price_type',
        'unit',
        'unit_of_measure',
        'quantity_on_hand',
        'expiry_date',
        'expiration_date',
        'mfg_date',
        'active_status',
        'mpn',
        'barcode_value',
        'lot_number',
        'item_image',
    ];

    private const NUMERIC_FIELDS = [
        'price',
        'original_price',
        'quantity_on_hand',
    ];

    private const DATE_FIELDS = [
        'expiry_date',
        'expiration_date',
        'mfg_date',
    ];

    private const IMAGE_FIELDS = [
        'item_image',
    ];

    /**
     * Record field-level item edits. Never throws — item saves must not fail because of logging.
     *
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     */
    public static function log(string $source, int $itemId, string $itemName, array $old, array $new): void
    {
        try {
            $changes = self::diff($old, $new);
            if ($changes === []) {
                return;
            }

            $user = Auth::user();
            $resolvedName = trim($itemName);
            if ($resolvedName === '') {
                $resolvedName = (string) ($new['item'] ?? $new['item_name'] ?? $old['item'] ?? $old['item_name'] ?? 'Unknown Item');
            }

            ItemEditLog::create([
                'user_id' => $user?->id,
                'user_email' => $user?->email ?: 'Unknown',
                'item_id' => $itemId,
                'item_name' => $resolvedName,
                'source' => $source,
                'changes' => $changes,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record item edit log', [
                'source' => $source,
                'item_id' => $itemId,
                'item_name' => $itemName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     * @return array<string, array{old: string|null, new: string|null}>
     */
    public static function diff(array $old, array $new): array
    {
        $changes = [];

        foreach (self::TRACKED_FIELDS as $field) {
            if (!array_key_exists($field, $new)) {
                continue;
            }

            $oldRaw = $old[$field] ?? null;
            $newRaw = $new[$field];

            if (in_array($field, self::IMAGE_FIELDS, true)) {
                if (self::normalizeText($oldRaw) === self::normalizeText($newRaw)) {
                    continue;
                }

                $changes[$field] = [
                    'old' => self::normalizeText($oldRaw) === null ? 'none' : 'previous image',
                    'new' => 'updated',
                ];
                continue;
            }

            if (in_array($field, self::NUMERIC_FIELDS, true)) {
                if (self::numericEquals($oldRaw, $newRaw)) {
                    continue;
                }

                $changes[$field] = [
                    'old' => self::formatNumeric($oldRaw),
                    'new' => self::formatNumeric($newRaw),
                ];
                continue;
            }

            if (in_array($field, self::DATE_FIELDS, true)) {
                $oldDate = self::normalizeDate($oldRaw);
                $newDate = self::normalizeDate($newRaw);
                if ($oldDate === $newDate) {
                    continue;
                }

                $changes[$field] = [
                    'old' => $oldDate,
                    'new' => $newDate,
                ];
                continue;
            }

            $oldText = self::normalizeText($oldRaw);
            $newText = self::normalizeText($newRaw);
            if ($oldText === $newText) {
                continue;
            }

            $changes[$field] = [
                'old' => $oldText,
                'new' => $newText,
            ];
        }

        return $changes;
    }

    private static function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private static function numericEquals(mixed $left, mixed $right): bool
    {
        $leftEmpty = self::isEmptyNumeric($left);
        $rightEmpty = self::isEmptyNumeric($right);

        if ($leftEmpty && $rightEmpty) {
            return true;
        }

        if ($leftEmpty || $rightEmpty) {
            return false;
        }

        return abs((float) $left - (float) $right) < 0.0001;
    }

    private static function isEmptyNumeric(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        return false;
    }

    private static function formatNumeric(mixed $value): ?string
    {
        if (self::isEmptyNumeric($value)) {
            return null;
        }

        $number = (float) $value;
        if (abs($number - round($number)) < 0.0001) {
            return (string) (int) round($number);
        }

        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($text)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $text;
        }
    }
}
