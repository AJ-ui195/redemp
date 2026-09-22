<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemEditLog extends Model
{
    protected $table = 'item_edit_logs';

    protected $fillable = [
        'user_id',
        'user_email',
        'item_id',
        'item_name',
        'source',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public const FIELD_LABELS = [
        'item' => 'Item Name',
        'item_name' => 'Item Name',
        'brand' => 'Brand',
        'description' => 'Description',
        'price' => 'Price',
        'original_price' => 'Original Price',
        'price_type' => 'Price Type',
        'unit' => 'Unit',
        'unit_of_measure' => 'Unit',
        'quantity_on_hand' => 'Quantity',
        'expiry_date' => 'Expiry Date',
        'expiration_date' => 'Expiry Date',
        'mfg_date' => 'Mfg Date',
        'active_status' => 'Status',
        'mpn' => 'Barcode',
        'barcode_value' => 'Barcode',
        'lot_number' => 'Lot Number',
        'item_image' => 'Image',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedDate(): string
    {
        if (!$this->created_at) {
            return 'Unknown';
        }

        return Carbon::parse($this->created_at)
            ->timezone('Asia/Manila')
            ->format('M d, Y h:i A');
    }

    /**
     * @return list<string>
     */
    public function formattedChanges(): array
    {
        $lines = [];

        foreach ($this->decodedChanges() as $field => $diff) {
            if (is_object($diff)) {
                $diff = (array) $diff;
            }
            if (!is_array($diff)) {
                continue;
            }

            $label = self::FIELD_LABELS[$field] ?? ucfirst(str_replace('_', ' ', (string) $field));
            $old = $this->displayValue($diff['old'] ?? null);
            $new = $this->displayValue($diff['new'] ?? null);
            $lines[] = "{$label}: {$old} → {$new}";
        }

        return $lines;
    }

    /**
     * Read the JSON column. Do not use $this->changes — that is Eloquent's dirty-attribute bag.
     *
     * @return array<string, mixed>
     */
    private function decodedChanges(): array
    {
        $changes = $this->getAttribute('changes');

        if (is_string($changes)) {
            $changes = json_decode($changes, true);
        }

        if (is_object($changes)) {
            $changes = json_decode(json_encode($changes), true);
        }

        return is_array($changes) ? $changes : [];
    }

    private function displayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return (string) $value;
    }

    /**
     * @return array{id: int, user_email: string, item_name: string, date: string, changes: list<string>}
     */
    public function toDisplayArray(): array
    {
        return [
            'id' => $this->id,
            'user_email' => (string) $this->user_email,
            'item_name' => (string) $this->item_name,
            'date' => $this->formattedDate(),
            'changes' => $this->formattedChanges(),
        ];
    }
}
