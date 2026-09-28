<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\ReviewService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bloc A — targeted tests for Missions 12–16, run against MySQL
 * (agronextzone_test, per phpunit.xml). No data destruction.
 */
class BlocACoreTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $producer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = User::factory()->create(['role' => 'client']);
        $this->producer = User::factory()->create(['role' => 'producer']);
    }

    private function product(int $price = 2000, int $stock = 50): Product
    {
        return Product::factory()->create([
            'producer_id' => $this->producer->id,
            'price' => $price,
            'stock_quantity' => $stock,
            'is_available' => true,
            'status' => 'published',
        ]);
    }

    /** A real paid order containing the given product for the client. */
    private function paidOrder(Product $product, int $qty = 2): Order
    {
        $order = Order::create([
            'client_id' => $this->client->id,
            'status' => 'preparing',
            'reference' => 'CMD-TEST-' . uniqid(),
            'payment_method' => 'MTN Mobile Money',
            'subtotal' => $product->price * $qty,
            'shipping_fee' => 1000,
            'total' => $product->price * $qty + 1000,
            'shipping_name' => 'Test Client',
            'shipping_phone' => '+237600000',
            'shipping_address' => 'Yaoundé',
            'placed_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'producer_id' => $this->producer->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'unit' => $product->unit,
            'quantity' => $qty,
            'line_total' => $product->price * $qty,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'client_id' => $this->client->id,
            'method' => 'mtn_mobile_money',
            'status' => 'paid',
            'amount' => $order->total,
            'provider_reference' => 'PAY-TEST-' . uniqid(),
            'paid_at' => now(),
        ]);

        return $order;
    }

    // ---------- MISSION 12 ----------

    public function test_m12_payment_uses_order_amount_and_unique_reference(): void
    {
        $product = $this->product();
        $order = $this->paidOrder($product);
        $order->payments()->delete(); // keep only the flow under test

        $service = app(PaymentService::class);
        $payment = $service->initiate($order->fresh());

        $this->assertEquals($order->total, $payment->amount); // from MySQL order
        $this->assertSame('pending', $payment->status);
        $this->assertStringStartsWith('PAY-', $payment->provider_reference);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);

        $other = $service->initiate($order->fresh());
        $this->assertSame($payment->id, $other->id); // no duplicate
    }

    public function test_m12_payment_idor_and_producer_blocked(): void
    {
        $order = $this->paidOrder($this->product());

        $this->actingAs($this->client)
            ->get(route('payment.show', ['reference' => 'CMD-DOES-NOT-EXIST']))
            ->assertNotFound();

        // Another client cannot see someone else's payment page (IDOR).
        $intruder = User::factory()->create(['role' => 'client']);
        $this->actingAs($intruder)
            ->get(route('payment.show', ['reference' => $order->reference]))
            ->assertForbidden();

        // A producer can never reach the payment flow.
        $this->actingAs($this->producer)
            ->get(route('payment.show', ['reference' => $order->reference]))
            ->assertForbidden();
    }

    public function test_m12_simulated_success_persists_paid_status(): void
    {
        $order = $this->paidOrder($this->product());
        $order->payments()->update(['status' => 'pending', 'paid_at' => null]);
        $payment = $order->payments()->first();

        app(PaymentService::class)->process($payment, 'successful');
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->client->id]);
    }

    // ---------- MISSION 13 ----------

    public function test_m13_only_real_buyer_can_review_once(): void
    {
        $product = $this->product();

        // Not purchased yet → refused.
        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $product->slug]), [
                'note' => 5, 'titre' => 'Test', 'commentaire' => 'Très bon produit.',
            ]);
        $this->assertDatabaseCount('reviews', 0);

        // Real purchase → review persisted.
        $this->paidOrder($product);
        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $product->slug]), [
                'note' => 5, 'titre' => 'Excellent', 'commentaire' => 'Fraîcheur au rendez-vous.',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id, 'client_id' => $this->client->id, 'rating' => 5,
        ]);

        // Duplicate refused.
        $this->actingAs($this->client)
            ->from(route('produit', ['slug' => $product->slug]))
            ->post(route('produit.avis', ['slug' => $product->slug]), [
                'note' => 4, 'titre' => 'Bis', 'commentaire' => 'Encore moi.',
            ]);
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_m13_producer_cannot_self_review(): void
    {
        $product = $this->product();

        $this->actingAs($this->producer)
            ->post(route('produit.avis', ['slug' => $product->slug]), [
                'note' => 5, 'titre' => 'Auto', 'commentaire' => 'Moi-même.',
            ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_m13_producer_score_computed_from_mysql(): void
    {
        $product = $this->product();
        Review::create([
            'product_id' => $product->id, 'client_id' => $this->client->id, 'rating' => 4,
            'status' => 'published', 'is_verified_purchase' => true,
        ]);
        $score = app(ReviewService::class)->producerScore($this->producer);
        $this->assertSame(4.0, $score['average']);
        $this->assertSame(1, $score['count']);
    }

    // ---------- MISSION 14 ----------

    public function test_m14_conversation_isolation_and_messages(): void
    {
        $otherProducer = User::factory()->create(['role' => 'producer']);
        $conversation = Conversation::create([
            'client_id' => $this->client->id,
            'producer_id' => $otherProducer->id,
            'last_message_at' => now(),
        ]);
        Message::create(['conversation_id' => $conversation->id, 'sender_id' => $this->client->id, 'body' => 'Bonjour']);

        // The client sees his conversation; another client CANNOT read it (IDOR):
        // he only gets his own, brand-new empty conversation — none of the
        // client's messages ever leak.
        $this->actingAs($this->client)->get(route('discussion', ['id' => $otherProducer->id]))->assertOk();
        $stranger = User::factory()->create(['role' => 'client']);
        $this->actingAs($stranger)
            ->get(route('discussion', ['id' => $otherProducer->id]))
            ->assertOk()
            ->assertDontSee('Bonjour', false);
        // The stranger got his OWN (empty) conversation — the client's message
        // stays in the client's conversation only.
        $this->assertSame(0, \App\Models\Conversation::query()
            ->where('client_id', $stranger->id)->withCount('messages')->get()
            ->sum('messages_count'));
        $this->assertDatabaseHas('conversations', ['client_id' => $this->client->id, 'producer_id' => $otherProducer->id]);

        // Sending persists a MySQL message.
        $this->actingAs($this->client)
            ->post(route('discussion.send', ['id' => $otherProducer->id]), ['message' => 'Dispo demain ?'])
            ->assertRedirect();
        $this->assertDatabaseHas('messages', ['conversation_id' => $conversation->id, 'body' => 'Dispo demain ?']);

        // unread → read for the recipient side.
        app(\App\Services\MessagingService::class)->markRead($otherProducer, $conversation->fresh());
        $this->assertDatabaseHas('messages', ['body' => 'Dispo demain ?', 'read_at' => now()->toDateTimeString()]);
    }

    // ---------- MISSION 15 ----------

    public function test_m15_notifications_isolated_per_user(): void
    {
        app(\App\Services\NotificationService::class)->send($this->client, 'order_status', 'Titre', 'Message');
        app(\App\Services\NotificationService::class)->send($this->producer, 'payment', 'Titre P', 'Message P');

        $clientNotifs = app(\App\Services\NotificationService::class)->forUser($this->client);
        $this->assertCount(1, $clientNotifs);
        $this->assertSame('Titre', $clientNotifs[0]['titre']);
        $this->assertFalse($clientNotifs[0]['lu']);

        // Mark read only affects the owner.
        app(\App\Services\NotificationService::class)->markAllRead($this->client);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->client->id]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $this->producer->id, 'read_at' => now()]);
        $this->assertSame(1, Notification::query()->where('notifiable_id', $this->producer->id)->whereNull('read_at')->count());
    }

    // ---------- MISSION 16 ----------

    public function test_m16_sale_transaction_from_real_order_no_duplicate(): void
    {
        $product = $this->product(price: 1500);
        $order = $this->paidOrder($product, qty: 2); // line_total = 3000

        $service = app(TransactionService::class);
        $payment = $order->payments()->first(); // paid → completed sale
        $trx = $service->recordSaleForOrder($order->fresh(), $this->producer->id, $payment);

        $this->assertSame(3000.0, (float) $trx->amount); // producer's own items only
        $this->assertSame('sale', $trx->type);

        // Second call = idempotent (no duplicate row).
        $again = $service->recordSaleForOrder($order->fresh(), $this->producer->id);
        $this->assertSame($trx->id, $again->id);
        $this->assertDatabaseCount('transactions', 1);

        $this->assertSame(3000.0, $service->revenue($this->producer));
    }

    public function test_m16_producer_cannot_record_sale_for_foreign_order(): void
    {
        $order = $this->paidOrder($this->product());
        $foreign = User::factory()->create(['role' => 'producer']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(TransactionService::class)->recordSaleForOrder($order->fresh(), $foreign->id);
    }
}
