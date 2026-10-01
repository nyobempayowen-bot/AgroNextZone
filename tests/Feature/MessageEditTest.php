<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la fonctionnalité de modification de messages.
 *
 * Couvre les 12 scénarios demandés dans la mission.
 */
class MessageEditTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $producer;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create(['role' => 'client']);
        $this->producer = User::factory()->create(['role' => 'producer']);

        // Crée la conversation client ↔ producteur.
        $this->conversation = Conversation::factory()->create([
            'client_id'   => $this->client->id,
            'producer_id' => $this->producer->id,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeMessage(User $sender, string $body = 'Message original'): Message
    {
        return Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id'       => $sender->id,
            'body'            => $body,
        ]);
    }

    private function patchMessage(Message $message, string $newBody, ?User $asUser = null): \Illuminate\Testing\TestResponse
    {
        $actor = $asUser ?? $this->client;
        return $this->actingAs($actor)->patch(
            route('message.update', ['message' => $message->id]),
            ['message' => $newBody]
        );
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    /** T1 : Envoi d'un message → OK */
    public function test_client_peut_envoyer_un_message(): void
    {
        $this->actingAs($this->client)->post(
            route('discussion.send', ['id' => $this->producer->id]),
            ['message' => 'Bonjour producteur !']
        )->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'sender_id'       => $this->client->id,
            'body'            => 'Bonjour producteur !',
        ]);
    }

    /** T2 : Client modifie son propre message → OK */
    public function test_client_peut_modifier_son_message(): void
    {
        $message = $this->makeMessage($this->client, 'Texte initial');

        $this->patchMessage($message, 'Texte modifié')
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'id'   => $message->id,
            'body' => 'Texte modifié',
        ]);
    }

    /** T3 : Le contenu modifié est bien enregistré en MySQL */
    public function test_contenu_modifie_persiste_en_base(): void
    {
        $message = $this->makeMessage($this->client, 'Avant');

        $this->patchMessage($message, 'Après modification');

        $this->assertEquals('Après modification', $message->fresh()->body);
    }

    /** T4 : Le champ edited_at est rempli après modification */
    public function test_message_est_marque_comme_modifie(): void
    {
        $message = $this->makeMessage($this->client);
        $this->assertNull($message->edited_at, 'edited_at doit être null avant modification');

        $this->patchMessage($message, 'Nouveau contenu');

        $this->assertNotNull($message->fresh()->edited_at, 'edited_at doit être non-null après modification');
    }

    /** T5 : Annulation (JS) → ancien contenu conservé (test serveur : pas de requête = pas de changement) */
    public function test_annulation_ne_modifie_pas_le_message(): void
    {
        $message = $this->makeMessage($this->client, 'Contenu original');

        // Aucune requête envoyée = contenu inchangé.
        $this->assertEquals('Contenu original', $message->fresh()->body);
        $this->assertNull($message->fresh()->edited_at);
    }

    /** T6 : Message vide → rejeté (422) */
    public function test_message_vide_est_rejete(): void
    {
        $message = $this->makeMessage($this->client, 'Texte valide');

        $this->patchMessage($message, '   ')
            ->assertStatus(422);

        // Le corps original doit être conservé.
        $this->assertEquals('Texte valide', $message->fresh()->body);
    }

    /** T7 : Client A tente de modifier le message de B → 403 */
    public function test_client_ne_peut_pas_modifier_message_autre_utilisateur(): void
    {
        // Le producteur envoie un message dans la conversation.
        $messageProducer = $this->makeMessage($this->producer, 'Message du producteur');

        // Le client tente de le modifier → 403.
        $this->patchMessage($messageProducer, 'Tentative piratage', $this->client)
            ->assertStatus(403);

        $this->assertEquals('Message du producteur', $messageProducer->fresh()->body);
    }

    /** T8 : Modification via ID manipulé (message d'une autre conversation) → 403 */
    public function test_modification_via_id_manipule_refusee(): void
    {
        $otherClient = User::factory()->create(['role' => 'client']);
        $otherConv = Conversation::factory()->create([
            'client_id'   => $otherClient->id,
            'producer_id' => $this->producer->id,
        ]);
        $otherMessage = Message::create([
            'conversation_id' => $otherConv->id,
            'sender_id'       => $otherClient->id,
            'body'            => 'Message privé autre conversation',
        ]);

        // Notre client tente de modifier le message de l'autre conversation.
        $this->patchMessage($otherMessage, 'Tentative', $this->client)
            ->assertStatus(403);

        $this->assertEquals('Message privé autre conversation', $otherMessage->fresh()->body);
    }

    /** T9 : Utilisateur extérieur à la conversation → refusé */
    public function test_utilisateur_exterieur_ne_peut_pas_modifier(): void
    {
        $outsider = User::factory()->create(['role' => 'client']);
        $message  = $this->makeMessage($this->client, 'Message privé');

        $this->actingAs($outsider)->patch(
            route('message.update', ['message' => $message->id]),
            ['message' => 'Intrusion']
        )->assertStatus(403);

        $this->assertEquals('Message privé', $message->fresh()->body);
    }

    /** T10 : Producteur peut modifier son propre message */
    public function test_producteur_peut_modifier_son_message(): void
    {
        $message = $this->makeMessage($this->producer, 'Réponse initiale');

        $this->actingAs($this->producer)->patch(
            route('message.update', ['message' => $message->id]),
            ['message' => 'Réponse modifiée']
        )->assertRedirect();

        $this->assertEquals('Réponse modifiée', $message->fresh()->body);
    }

    /** T11 : Les anciens messages (edited_at = null) restent inchangés */
    public function test_messages_non_modifies_restent_intacts(): void
    {
        $msg1 = $this->makeMessage($this->client, 'Premier message');
        $msg2 = $this->makeMessage($this->client, 'Deuxième message');

        // On modifie uniquement le premier.
        $this->patchMessage($msg1, 'Premier message modifié');

        // Le second ne doit pas être touché.
        $this->assertEquals('Deuxième message', $msg2->fresh()->body);
        $this->assertNull($msg2->fresh()->edited_at);
    }

    /** T12 : Non-régression — l'envoi de messages fonctionne toujours */
    public function test_envoi_message_non_regression(): void
    {
        $this->actingAs($this->client)->post(
            route('discussion.send', ['id' => $this->producer->id]),
            ['message' => 'Test non-régression']
        )->assertRedirect(route('discussion', ['id' => $this->producer->id]));

        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->client->id,
            'body'      => 'Test non-régression',
        ]);
    }
}
