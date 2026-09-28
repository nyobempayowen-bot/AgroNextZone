<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

/**
 * Bloc B — Conversation authorization.
 *
 * Only the client or the producer taking part in the conversation may
 * read it or write a message inside it.
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return (int) $conversation->client_id === (int) $user->id
            || (int) $conversation->producer_id === (int) $user->id;
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }
}
