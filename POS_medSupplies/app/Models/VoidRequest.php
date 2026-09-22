<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class VoidRequest extends Model
{
    protected $fillable = [
        'sale_id',
        'requested_by',
        'requested_by_user_id',
        'approved_by',
        'reviewed_by_user_id',
        'status',
        'request_status_id',
        'reason',
        'rejection_reason',
        'review_notes',
        'approved_at',
        'rejected_at',
        'reviewed_at',
        'voided_items',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'voided_items' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VoidRequestItem::class);
    }

    public function requestStatus(): BelongsTo
    {
        return $this->belongsTo(RequestStatus::class, 'request_status_id');
    }

    public function requestedBy(): BelongsTo
    {
        $key = Schema::hasColumn($this->getTable(), 'requested_by_user_id')
            ? 'requested_by_user_id'
            : 'requested_by';

        return $this->belongsTo(User::class, $key);
    }

    public function approvedBy(): BelongsTo
    {
        $key = Schema::hasColumn($this->getTable(), 'reviewed_by_user_id')
            ? 'reviewed_by_user_id'
            : 'approved_by';

        return $this->belongsTo(User::class, $key);
    }

    public function getStatusAttribute(): ?string
    {
        if (array_key_exists('status', $this->attributes) && $this->attributes['status'] !== null) {
            return $this->attributes['status'];
        }

        if ($this->relationLoaded('requestStatus') && $this->requestStatus) {
            return $this->requestStatus->name;
        }

        if (Schema::hasColumn($this->getTable(), 'request_status_id') && $this->request_status_id) {
            return RequestStatus::query()->where('id', $this->request_status_id)->value('name');
        }

        return null;
    }

    public function scopePending($query)
    {
        if (Schema::hasColumn((new static)->getTable(), 'request_status_id')) {
            $pendingId = RequestStatus::query()->where('name', 'pending')->value('id');

            return $query->where('request_status_id', $pendingId);
        }

        return $query->where('status', 'pending');
    }
}
