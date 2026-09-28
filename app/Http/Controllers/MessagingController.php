<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use App\Services\MessagingService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Mission 14 — MySQL-backed Client ↔ Producer messaging.
 *
 * Isolation: every conversation access goes through
 * MessagingService::assertParticipant(), so a user can never open,
 * read or write another user's conversation (IDOR-safe).
 */
class MessagingController extends Controller
{
    public function __construct(
        private MessagingService $messaging,
        private NotificationService $notifications,
    ) {}

    /** Inbox: my conversations only. */
    public function index(): View
    {
        $user = Auth::user();

        // Construit la liste des conversations de l'utilisateur,
        // avec l'interlocuteur, les messages et le dernier message.
        $conversations = collect($this->messaging->conversationsFor($user))->map(
            function (Conversation $conversation) use ($user): array {
                // Détermine l'autre participant : producteur si je suis le client, sinon client.
                $other = (int) $conversation->client_id === (int) $user->id
                    ? $conversation->producer
                    : $conversation->client;
                $last = $conversation->messages->last();

                return [
                    'conversation' => $conversation,
                    'producteur' => [
                        'id' => $other?->id,
                        'nom' => $other?->name ?? 'Producteur',
                        // Avatar par défaut si l'utilisateur n'en a pas.
                        'avatar' => $other?->avatar_url
                            ?? 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=500&q=80',
                    ],
                    // Chaque message est étiqueté 'me' ou 'other' selon l'expéditeur.
                    'messages' => $conversation->messages
                        ->map(fn ($m) => ['sender' => $m->sender_id === $user->id ? 'me' : 'other', 'text' => $m->body, 'time' => $m->created_at->format('H:i')]),
                    'dernier_message' => $last ? ['text' => $last->body, 'time' => $last->created_at->format('H:i')] : null,
                ];
            }
        )->all();

        return view('messagerie', [
            'conversations' => $conversations,
            // Liste des producteurs pour démarrer une nouvelle conversation.
            'producteurs' => User::query()->where('role', 'producer')->get(['id', 'name']),
            'user' => $user,
        ]);
    }

    /** Open (or create) the conversation with one user; marks his messages read. */
    public function show(string $id): View
    {
        $user = Auth::user();
        $otherUser = User::query()->whereKey((int) $id)->firstOrFail();

        // Récupère ou crée la conversation (toujours client en premier, producteur en second)
        if ($user->role === 'producer') {
            $conversation = $this->messaging->conversationBetween($otherUser, $user);
        } else {
            $conversation = $this->messaging->conversationBetween($user, $otherUser);
        }
        
        \Illuminate\Support\Facades\Gate::authorize('view', $conversation);
        $this->messaging->assertParticipant($user, $conversation);
        $this->messaging->markRead($user, $conversation);

        // Formate les messages avec expéditeur ('me'/'other') et heure.
        $messages = collect($this->messaging->messagesFor($conversation))->map(
            fn ($m) => ['sender' => (int) $m->sender_id === (int) $user->id ? 'me' : 'other', 'text' => $m->body, 'time' => $m->created_at->format('H:i')]
        )->all();

        return view('discussion', [
            'producteur' => [
                'id' => $otherUser->id,
                'nom' => $otherUser->name,
                'avatar' => $otherUser->avatar_url
                    ?? 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=500&q=80',
            ],
            'messages' => $messages,
            'producerId' => $otherUser->id,
            'user' => $user,
        ]);
    }

    /** Send one message inside MY conversation with this user. */
    public function send(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $user = Auth::user();
        $otherUser = User::query()->whereKey((int) $id)->firstOrFail();

        if ($user->role === 'producer') {
            $conversation = $this->messaging->conversationBetween($otherUser, $user);
        } else {
            $conversation = $this->messaging->conversationBetween($user, $otherUser);
        }

        \Illuminate\Support\Facades\Gate::authorize('sendMessage', $conversation);
        $this->messaging->sendMessage($conversation, $user, $validated['message']);

        $recipient = (int) $conversation->client_id === (int) $user->id
            ? $conversation->producer
            : $conversation->client;
        $this->notifications->send($recipient, 'message', 'Nouveau message', "Vous avez reçu un nouveau message de {$user->name}.");

        return redirect()->route('discussion', ['id' => $otherUser->id]);
    }
}
