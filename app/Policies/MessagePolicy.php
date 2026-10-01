<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

/**
 * MessagePolicy — protège la modification d'un message.
 *
 * Règles :
 *  - Seul l'expéditeur du message peut le modifier (sender_id === user.id).
 *  - Aucune vérification d'ID dans l'URL n'est suffisante : le modèle est
 *    résolu via Eloquent et la contrainte sender_id est vérifiée ici.
 */
class MessagePolicy
{
    /**
     * L'utilisateur peut modifier ce message uniquement s'il en est l'auteur.
     */
    public function update(User $user, Message $message): bool
    {
        return (int) $message->sender_id === (int) $user->id;
    }
}
