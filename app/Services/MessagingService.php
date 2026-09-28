<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Mission 14 — persistent MySQL conversations & messages (Client ↔ Producer).
 *
 * Isolation is enforced here: a conversation can only ever be read or
 * written by its own client or its own producer. Any other user gets 403.
 */
class MessagingService
{
    /**
     * Find or create the unique conversation between a client and a producer.
     */
    public function conversationBetween(User $client, User $producer): Conversation
    {
        return Conversation::query()->firstOrCreate(
            ['client_id' => $client->id, 'producer_id' => $producer->id],
            ['last_message_at' => now()],
        );
    }

    /**
     * IDOR gate: the user must be the client or the producer of the conversation.
     */
    public function assertParticipant(User $user, Conversation $conversation): void
    {
        if ((int) $conversation->client_id !== (int) $user->id
            && (int) $conversation->producer_id !== (int) $user->id) {
            abort(403, 'Vous ne pouvez pas accéder à cette conversation.');
        }
    }

    public function sendMessage(Conversation $conversation, User $sender, string $body): Message
    {
        $this->assertParticipant($sender, $conversation);

        return DB::transaction(function () use ($conversation, $sender, $body) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'body' => trim($body),
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);

            return $message;
        });
    }

    /** Chronological messages of a conversation the user is part of. */
    public function messagesFor(Conversation $conversation): array
    {
        return $conversation->messages()->with('sender')->orderBy('id')->get()->all();
    }

    /** Mark the messages sent by the OTHER participant as read (lu/non-lu). */
    public function markRead(User $reader, Conversation $conversation): int
    {
        $this->assertParticipant($reader, $conversation);

        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** Unread count for one side of the inbox. */
    public function unreadCount(User $user): int
    {
        return Message::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->whereHas('conversation', fn ($q) => $q
                ->where('client_id', $user->id)
                ->orWhere('producer_id', $user->id))
            ->count();
    }

    /** The user's own conversations only, for the /messagerie inbox. */
    public function conversationsFor(User $user): array
    {
        return Conversation::query()
            ->with(['client', 'producer', 'messages.sender'])
            ->where('client_id', $user->id)->orWhere('producer_id', $user->id)
            ->orderByDesc('last_message_at')
            ->get()
            ->filter(fn (Conversation $c) => (int) $c->client_id === (int) $user->id
                || (int) $c->producer_id === (int) $user->id)
            ->values()
            ->all();
    }
}
