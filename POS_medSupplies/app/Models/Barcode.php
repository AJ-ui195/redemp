<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class Barcode extends Model
{
    protected $fillable = [
        'barcode_value',
        'item_name',
        'item_image',
        'price',
        'original_price',
        'costing_price',
        'price_type',
        'unit',
        'barcode_type',
        'expiration_date',
        'mfg_date',
        'active_status',
        'description',
        'quantity_on_hand',
        'brand',
        'lot_number',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'original_price' => 'decimal:4',
        'costing_price' => 'decimal:4',
        'quantity_on_hand' => 'decimal:4',
        'expiration_date' => 'date',
        'mfg_date' => 'date',
    ];

    /** @var list<string>|null */
    private static ?array $columnCache = null;

    protected static function booted(): void
    {
        static::saving(function (Barcode $barcode) {
            $barcode->alignOriginalPriceForDatabase();
        });
    }

    /**
     * Hostinger may not have barcodes.original_price. Keep the save working
     * and still store original price on inventory_products.
     */
    public function alignOriginalPriceForDatabase(): void
    {
        self::ensureOriginalPriceColumn();

        $filtered = self::attributesForExistingColumns($this->getAttributes());

        if (array_key_exists('original_price', $this->getAttributes())
            && !array_key_exists('original_price', $filtered)
        ) {
            unset($this->attributes['original_price']);
            $this->offsetUnset('original_price');
        }

        if (array_key_exists('costing_price', $filtered)
            && !array_key_exists('costing_price', $this->getAttributes())
        ) {
            $this->attributes['costing_price'] = $filtered['costing_price'];
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>|null  $columns
     * @return array<string, mixed>
     */
    public static function attributesForExistingColumns(array $attributes, ?array $columns = null): array
    {
        $has = array_flip($columns ?? self::columnNames());

        if (!array_key_exists('original_price', $attributes)) {
            return $attributes;
        }

        if (isset($has['original_price'])) {
            return $attributes;
        }

        $value = $attributes['original_price'];
        unset($attributes['original_price']);

        if (isset($has['costing_price'])) {
            $attributes['costing_price'] = $value;
        }

        return $attributes;
    }

    public function getOriginalPriceAttribute($value)
    {
        if ($value !== null && $value !== '') {
            return $value;
        }

        return $this->attributes['costing_price'] ?? $value;
    }

    public static function ensureOriginalPriceColumn(): void
    {
        static $attempted = false;
        if ($attempted) {
            return;
        }
        $attempted = true;

        try {
            if (self::hasDbColumn('original_price') || self::hasDbColumn('costing_price')) {
                return;
            }

            Schema::table('barcodes', function (Blueprint $table) {
                $table->decimal('original_price', 18, 4)->nullable();
            });
            self::$columnCache = null;
        } catch (\Throwable $e) {
            self::$columnCache = null;
            if (self::hasDbColumn('original_price') || self::hasDbColumn('costing_price')) {
                return;
            }

            Log::warning('Could not add barcodes.original_price; saves will skip that column.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function hasDbColumn(string $column): bool
    {
        return isset(array_flip(self::columnNames())[$column]);
    }

    /**
     * @return list<string>
     */
    private static function columnNames(): array
    {
        if (self::$columnCache === null) {
            try {
                self::$columnCache = Schema::getColumnListing('barcodes');
            } catch (\Throwable $e) {
                self::$columnCache = [];
            }
        }

        return self::$columnCache;
    }
}
