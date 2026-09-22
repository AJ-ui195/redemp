<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id',
        'payment_method_id',
        'amount',
        'discount_type',
        'is_senior_citizen',
        'is_pwd',
        'discount_amount',
        'customer_name',
        'id_number',
        'id_type',
        'issuing_lgu',
        'senior_pwd_discount_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'is_senior_citizen' => 'boolean',
        'is_pwd' => 'boolean',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function seniorPwdDiscount(): BelongsTo
    {
        return $this->belongsTo(SeniorPwdDiscount::class, 'senior_pwd_discount_id');
    }
}
