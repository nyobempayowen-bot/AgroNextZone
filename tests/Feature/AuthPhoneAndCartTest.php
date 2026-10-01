<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPhoneAndCartTest extends TestCase
{
    // RefreshDatabase : remet la base de données à zéro avant chaque test,
    // afin que chaque test parte d'un état propre et indépendant.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Since products are now in MySQL, we need to seed the required products
        // for these tests (cacao-fermente-qualite-superieure).
        // ProductSeeder requires a producer to exist.
        User::factory()->create(['role' => 'producer', 'id' => 999]);
        $this->seed(\Database\Seeders\ProductSeeder::class);
    }

    /**
     * Test 1 — Validation du champ téléphone à l'inscription.
     * Un numéro contenant des lettres doit être rejeté.
     */
    public function test_phone_field_rejects_letters(): void
    {
        // Simule une requête POST vers /register depuis la page d'inscription,
        // avec un numéro de téléphone invalide (contenant des lettres).
        $response = $this->from('/register')->post('/register', [
            'name' => 'Client Test',
            'email' => 'phonefail@example.com',
            'phone' => '0650ABCD12',
            'role' => 'client',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Vérifie que la validation a bien rejeté le champ 'phone'
        // (erreur stockée en session, l'inscription est refusée).
        $response->assertSessionHasErrors('phone');
    }

    /**
     * Test 2 — Logique de connexion : identifiants valides.
     * Un client qui saisit le bon email + mot de passe est connecté
     * et redirigé vers la page d'accueil (marketplace).
     */
    public function test_client_login_redirects_to_marketplace(): void
    {
        // Crée un utilisateur client en base via une factory,
        // avec un mot de passe chiffré (bcrypt).
        $user = User::factory()->create([
            'email' => 'client2@example.com',
            'role' => 'client',
            'password' => bcrypt('Password123!'),
        ]);

        // Simule la soumission du formulaire de connexion
        // avec les mêmes identifiants que l'utilisateur créé.
        $response = $this->from('/login')->post('/login', [
            'email' => 'client2@example.com',
            'password' => 'Password123!',
        ]);

        // La connexion réussit si l'utilisateur est redirigé vers '/' (marketplace).
        $response->assertRedirect('/');
    }

    /**
     * Test 3 — Accès au panier pour un client authentifié.
     * La session utilisateur fonctionne et la page du panier s'affiche.
     */
    public function test_client_can_visit_cart_page(): void
    {
        // Crée un client directement authentifié (rôle 'client').
        $client = User::factory()->create(['role' => 'client']);

        // actingAs() connecte l'utilisateur pour la requête : simule une session active.
        $response = $this->actingAs($client)->get('/panier');

        // Vérifie que la page répond avec succès (HTTP 200)
        // et que le contenu 'Mon panier' est bien présent.
        $response->assertStatus(200)
            ->assertSee('Mon panier');
    }

    /**
     * Test 4 — Ajout d'un produit au panier.
     * Un client connecté peut ajouter un produit : il est redirigé
     * vers le panier et celui-ci n'est plus vide.
     */
    public function test_product_can_be_added_to_cart(): void
    {
        // Crée un client authentifié.
        $client = User::factory()->create(['role' => 'client']);

        // Ajoute le produit (identifié par son slug) au panier via POST.
        $response = $this->actingAs($client)
            ->from('/')
            ->post('/panier/ajouter/cacao-fermente-qualite-superieure');

        // ET que la base de données contient bien un panier pour cet utilisateur.
        $response->assertRedirect('/panier');
        $this->assertDatabaseHas('carts', ['user_id' => $client->id]);
    }

    /**
     * Test 5 — Protection des routes d'achat contre les visiteurs.
     * Sans authentification, toute route d'achat renvoie 401 (Non autorisé).
     */
    public function test_visitor_cannot_access_purchase_routes(): void
    {
        // Chaque route d'achat est testée sans utilisateur connecté :
        // elles doivent toutes rediriger vers /login (anciennement 401).
        $this->get('/panier')->assertRedirect('/login');
        $this->post('/panier/ajouter/cacao-fermente-qualite-superieure')->assertRedirect('/login');
        $this->get('/checkout')->assertRedirect('/login');
        $this->post('/checkout')->assertRedirect('/login');
        $this->post('/produit/cacao-fermente-qualite-superieure/acheter')->assertRedirect('/login');
        $this->get('/mes-commandes')->assertRedirect('/login');
    }

    /**
     * Test 6 — Contrôle des rôles : le producteur peut consulter mais pas acheter.
     * Il accède aux fiches produits (200) mais toutes les routes d'achat
     * lui sont interdites (403 Forbidden).
     */
    public function test_producer_can_consult_products_but_cannot_buy(): void
    {
        // Crée un utilisateur producteur authentifié.
        $producer = User::factory()->create(['role' => 'producer']);

        // Le producteur PEUT consulter la fiche du produit (HTTP 200).
        $this->actingAs($producer)
            ->get('/produit/cacao-fermente-qualite-superieure')
            ->assertOk()
            ->assertSee('Cacao fermenté qualité supérieure');

        // Mais toute action d'achat lui est INTERDITE (HTTP 403) :
        // panier, ajout, checkout, achat direct et commandes.
        $this->actingAs($producer)->get('/panier')->assertForbidden();
        $this->actingAs($producer)->post('/panier/ajouter/cacao-fermente-qualite-superieure')->assertForbidden();
        $this->actingAs($producer)->get('/checkout')->assertForbidden();
        $this->actingAs($producer)->post('/checkout')->assertForbidden();
        $this->actingAs($producer)->post('/produit/cacao-fermente-qualite-superieure/acheter')->assertForbidden();
        $this->actingAs($producer)->get('/mes-commandes')->assertForbidden();
    }
}
