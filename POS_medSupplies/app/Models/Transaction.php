<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'sale_id',
        'user_id',
        'type',
        'amount',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateTransactionNumber(): string
    {
        $prefix = 'TXN';
        $date = date('Ymd');
        $count = 1;

        try {
            if (Schema::hasTable((new self())->getTable())) {
                $count = self::whereDate('created_at', today())->count() + 1;
            }
        } catch (\Throwable $e) {
            $count = ((int) (microtime(true) * 1000)) % 100000;
        }

        return sprintf('%s-%s-%05d', $prefix, $date, $count);
    }
}
