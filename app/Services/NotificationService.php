<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Mission 15 — persistent MySQL notifications (Laravel morphs schema).
 *
 * Every business event (order status change, payment result, message
 * received, review posted) creates a Notification row scoped to ONE
 * user. Reading is always filtered by notifiable — no cross-user leak.
 */
class NotificationService
{
    /** Create + persist a notification for a single user. */
    public function send(User $user, string $type, string $title, string $message): Notification
    {
        return Notification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'notifiable_id' => $user->id,
            'notifiable_type' => $user::class,
            'data' => json_encode(['titre' => $title, 'message' => $message], JSON_UNESCAPED_UNICODE),
        ]);
    }

    /** The user's own notifications only, Blade-shaped (id/titre/message/temps/lu). */
    public function forUser(User $user): array
    {
        return Notification::query()
            ->where('notifiable_type', $user::class)
            ->where('notifiable_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Notification $n): array => [
                'id' => $n->id,
                'type' => $n->type,
                'titre' => $this->dataOf($n)['titre'] ?? '',
                'message' => $this->dataOf($n)['message'] ?? '',
                'temps' => $n->created_at->diffForHumans(),
                'lu' => $n->read_at !== null,
            ])
            ->all();
    }

    /** JSON data of a notification, decoded defensively. */
    private function dataOf(Notification $n): array
    {
        $data = $n->data;

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return is_array($data) ? $data : [];
    }

    public function unreadCount(User $user): int
    {
        return Notification::query()
            ->where('notifiable_type', $user::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /** Mark ALL of this user's notifications read — never anyone else's. */
    public function markAllRead(User $user): int
    {
        return Notification::query()
            ->where('notifiable_type', $user::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
