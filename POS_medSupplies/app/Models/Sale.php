<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'sale_number',
        'user_id',
        'cashier_user_id',
        'shift_id',
        'cashier_shift_id',
        'amount',
        'items',
        'subtotal',
        'tax',
        'discount',
        'discount_type',
        'id_number',
        'id_type',
        'issuing_lgu',
        'customer_name',
        'payment_method',
        'amount_tendered',
        'change_amount',
        'customer_email',
        'status',
        'sale_status_id',
        'sold_at',
        'voided_items',
    ];

    protected $casts = [
        'items' => 'array',
        'voided_items' => 'array',
        'amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'amount_tendered' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    protected $appends = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->cashierUserForeignKey());
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function saleStatus(): BelongsTo
    {
        return $this->belongsTo(SaleStatus::class, 'sale_status_id');
    }

    public function shift(): BelongsTo
    {
        if (Schema::hasColumn($this->getTable(), 'cashier_shift_id')) {
            return $this->belongsTo(CashierShift::class, 'cashier_shift_id');
        }

        return $this->belongsTo(Shift::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function salePayments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function payments(): HasMany
    {
        if (Schema::hasTable('sale_payments')) {
            return $this->hasMany(SalePayment::class);
        }

        return $this->hasMany(Payment::class);
    }

    public function getStatusAttribute(): ?string
    {
        if (array_key_exists('status', $this->attributes) && $this->attributes['status'] !== null) {
            return $this->attributes['status'];
        }

        if ($this->relationLoaded('saleStatus') && $this->saleStatus) {
            return $this->saleStatus->name;
        }

        if (Schema::hasColumn($this->getTable(), 'sale_status_id') && $this->sale_status_id) {
            return SaleStatus::query()->where('id', $this->sale_status_id)->value('name');
        }

        return null;
    }

    public function getAmountAttribute(): float
    {
        if (array_key_exists('amount', $this->attributes) && $this->attributes['amount'] !== null) {
            return (float) $this->attributes['amount'];
        }

        if ($this->relationLoaded('salePayments')) {
            return (float) $this->salePayments->sum('amount');
        }

        if (Schema::hasTable('sale_payments')) {
            return (float) $this->salePayments()->sum('amount');
        }

        if ($this->relationLoaded('saleItems')) {
            return (float) $this->saleItems->sum(fn ($item) => $item->unit_price * max(0, $item->quantity - ($item->voided_quantity ?? 0)));
        }

        return 0.0;
    }

    public function scopeCompleted($query)
    {
        if (Schema::hasColumn($this->getTable(), 'sale_status_id')) {
            $completedId = SaleStatus::query()->where('name', 'completed')->value('id');

            return $query->where('sale_status_id', $completedId);
        }

        return $query->where('status', 'completed');
    }

    /**
     * Sum sale totals for a query builder (supports legacy amount column or sale_payments).
     */
    public static function sumAmount($query): float
    {
        if (Schema::hasColumn((new static)->getTable(), 'amount')) {
            return (float) ($query->sum('amount') ?? 0);
        }

        $ids = (clone $query)->pluck('id');

        if ($ids->isEmpty()) {
            return 0.0;
        }

        return (float) SalePayment::query()
            ->whereIn('sale_id', $ids)
            ->sum('amount');
    }

    public static function generateReceiptNumber(): string
    {
        $prefix = 'SALE';
        $count = self::count() + 1;

        return sprintf('%s-%06d', $prefix, $count);
    }

    private function cashierUserForeignKey(): string
    {
        return Schema::hasColumn($this->getTable(), 'cashier_user_id')
            ? 'cashier_user_id'
            : 'user_id';
    }
}
