<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DamageRequest extends Model
{
    protected $fillable = [
        'requested_by_user_id',
        'request_status_id',
        'reason',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DamageRequestItem::class);
    }

    public function requestStatus(): BelongsTo
    {
        return $this->belongsTo(RequestStatus::class, 'request_status_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function getStatusAttribute(): ?string
    {
        if ($this->relationLoaded('requestStatus') && $this->requestStatus) {
            return $this->requestStatus->name;
        }

        if ($this->request_status_id) {
            return RequestStatus::query()->where('id', $this->request_status_id)->value('name');
        }

        return null;
    }

    public function scopePending($query)
    {
        $pendingId = RequestStatus::query()->where('name', 'pending')->value('id');

        return $query->where('request_status_id', $pendingId);
    }
}
