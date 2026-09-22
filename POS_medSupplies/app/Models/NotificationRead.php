<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRead extends Model
{
    protected $table = 'notification_reads';

    protected $fillable = [
        'user_id',
        'notification_type',
        'notification_key',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Get the user who read the notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark a notification as read
     */
    public static function markAsRead($userId, $type, $key)
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'notification_type' => $type,
                'notification_key' => $key,
            ],
            [
                'read_at' => now(),
            ]
        );
    }

    /**
     * Check if a notification is read
     */
    public static function isRead($userId, $type, $key): bool
    {
        return self::where('user_id', $userId)
            ->where('notification_type', $type)
            ->where('notification_key', $key)
            ->exists();
    }

    /**
     * Mark all notifications as read for a user
     */
    public static function markAllAsRead($userId, $types = [])
    {
        $now = now();
        $inserts = [];
        
        foreach ($types as $type => $keys) {
            foreach ($keys as $key) {
                $inserts[] = [
                    'user_id' => $userId,
                    'notification_type' => $type,
                    'notification_key' => $key,
                    'read_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        
        if (!empty($inserts)) {
            // Use insertOrIgnore to avoid duplicates
            foreach ($inserts as $insert) {
                self::firstOrCreate(
                    [
                        'user_id' => $insert['user_id'],
                        'notification_type' => $insert['notification_type'],
                        'notification_key' => $insert['notification_key'],
                    ],
                    [
                        'read_at' => $insert['read_at'],
                    ]
                );
            }
        }
    }
}
