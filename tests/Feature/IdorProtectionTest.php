<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Bloc B — IDOR intrusion tests.
 *
 * For every sensitive resource, a user acting on ANOTHER user's resource
 * must be refused (403 / Gate denied), while acting on HIS OWN resource
 * must succeed.
 */
class IdorProtectionTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------- Orders ------------------------------- */

    public function test_client_cannot_view_or_pay_another_clients_order(): void
    {
        $alice = User::factory()->create(['role' => 'client']);
        $bob = User::factory()->create(['role' => 'client']);

        $bobOrder = Order::create([
            'client_id' => $bob->id,
            'reference' => 'CMD-IDOR-001',
            'status' => 'En attente',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'total' => 1000,
            'payment_method' => 'mtn_momo',
            'shipping_name' => 'Bob',
            'shipping_phone' => '650000001',
            'shipping_address' => 'Yaoundé',
        ]);

        $this->actingAs($alice)
            ->get('/paiement/' . $bobOrder->reference)
            ->assertForbidden();

        $this->actingAs($alice)
            ->get('/paiement/' . $bobOrder->reference . '/successful')
            ->assertForbidden();
    }

    public function test_client_can_view_his_own_order_payment(): void
    {
        $alice = User::factory()->create(['role' => 'client']);

        $order = Order::create([
            'client_id' => $alice->id,
            'reference' => 'CMD-IDOR-002',
            'status' => 'En attente',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'total' => 1000,
            'payment_method' => 'mtn_momo',
            'shipping_name' => 'Alice',
            'shipping_phone' => '650000002',
            'shipping_address' => 'Yaoundé',
        ]);

        $this->actingAs($alice)
            ->get('/paiement/' . $order->reference)
            ->assertOk()
            ->assertSee($order->reference);
    }

    public function test_producer_cannot_change_status_of_a_foreign_order(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $alice = User::factory()->create(['role' => 'producer']);
        $bob = User::factory()->create(['role' => 'producer']);

        $product = Product::factory()->create(['producer_id' => $alice->id]);

        $order = Order::create([
            'client_id' => $client->id,
            'reference' => 'CMD-IDOR-003',
            'status' => 'En attente',
            'subtotal' => 1000,
            'shipping_fee' => 0,
            'total' => 1000,
            'payment_method' => 'mtn_momo',
            'shipping_name' => 'Client',
            'shipping_phone' => '650000003',
            'shipping_address' => 'Yaoundé',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'producer_id' => $alice->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 1000,
            'line_total' => 1000,
            'unit' => $product->unit,
        ]);

        // Bob has no product in this order → forbidden.
        $this->actingAs($bob)
            ->post('/producer/orders/' . $order->reference . '/status', ['statut' => 'Livrée'])
            ->assertForbidden();

        // Alice owns the sold product → allowed.
        $this->actingAs($alice)
            ->post('/producer/orders/' . $order->reference . '/status', ['statut' => 'En préparation'])
            ->assertRedirect();
    }

    /* ------------------------------- Products ------------------------------ */

    public function test_producer_cannot_modify_or_delete_another_producers_offer(): void
    {
        $alice = User::factory()->create(['role' => 'producer']);
        $bob = User::factory()->create(['role' => 'producer']);

        $product = Product::factory()->create(['producer_id' => $alice->id]);

        $this->actingAs($bob)
            ->post('/producer/products/' . $product->slug . '/toggle')
            ->assertForbidden();

        $this->actingAs($bob)
            ->post('/producer/products/' . $product->slug . '/update', ['nom' => 'Hack'])
            ->assertForbidden();

        $this->actingAs($bob)
            ->delete('/producer/products/' . $product->slug)
            ->assertForbidden();

        // Alice owns the offer → allowed.
        $this->actingAs($alice)
            ->post('/producer/products/' . $product->slug . '/toggle')
            ->assertRedirect();
    }

    /* ---------------------------- Conversations ---------------------------- */

    public function test_user_cannot_access_a_conversation_he_is_not_part_of(): void
    {
        $alice = User::factory()->create(['role' => 'client']);
        $bob = User::factory()->create(['role' => 'client']);
        $producer = User::factory()->create(['role' => 'producer']);

        $conversation = Conversation::create([
            'client_id' => $alice->id,
            'producer_id' => $producer->id,
        ]);

        $this->actingAs($bob);
        $this->assertFalse(Gate::allows('view', $conversation));
        $this->assertFalse(Gate::allows('sendMessage', $conversation));

        $this->actingAs($alice);
        $this->assertTrue(Gate::allows('view', $conversation));
        $this->assertTrue(Gate::allows('sendMessage', $conversation));
    }

    /* ------------------------------- Reviews ------------------------------- */

    public function test_client_cannot_edit_or_delete_another_clients_review(): void
    {
        $alice = User::factory()->create(['role' => 'client']);
        $bob = User::factory()->create(['role' => 'client']);
        $product = Product::factory()->create();

        $review = Review::create([
            'product_id' => $product->id,
            'client_id' => $alice->id,
            'rating' => 5,
            'title' => 'Top',
            'comment' => 'Excellent produit.',
            'status' => 'published',
        ]);

        $this->actingAs($bob);
        $this->assertFalse(Gate::allows('update', $review));
        $this->assertFalse(Gate::allows('delete', $review));

        $this->actingAs($alice);
        $this->assertTrue(Gate::allows('update', $review));
        $this->assertTrue(Gate::allows('delete', $review));
    }

    /* ---------------------------- User profiles ---------------------------- */

    public function test_user_cannot_update_another_users_profile(): void
    {
        $alice = User::factory()->create(['role' => 'client']);
        $bob = User::factory()->create(['role' => 'client']);

        $this->actingAs($alice);
        $this->assertFalse(Gate::allows('update', $bob));
        $this->assertTrue(Gate::allows('update', $alice));
    }

    public function test_user_cannot_self_assign_the_admin_role(): void
    {
        // Registration endpoint: role is constrained to client|producer.
        $this->post('/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'phone' => '650000004',
            'role' => 'admin',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com', 'role' => 'admin']);

        // Profile update endpoint: a client posting role=admin stays client.
        $alice = User::factory()->create(['role' => 'client', 'name' => 'Alice']);

        $this->actingAs($alice)
            ->post('/client/account', [
                'name' => 'Alice Admin ?',
                'phone' => '650000005',
                'role' => 'admin',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $alice->id,
            'role' => 'client',
        ]);

        // Policy: changeRole is never allowed through profile updates.
        $this->actingAs($alice);
        $this->assertFalse(Gate::allows('changeRole', $alice));
    }
}
