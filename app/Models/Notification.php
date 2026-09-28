<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

/**
 * Mission 15 — uses the standard Laravel `notifications` table
 * (uuid id, morphs notifiable, json data, read_at).
 */
class Notification extends DatabaseNotification
{
    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];
}
