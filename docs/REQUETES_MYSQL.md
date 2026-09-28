# Requêtes MySQL de l'application — classification par priorité

Application : **AgroNextZone** (Laravel 11 / Eloquent / MySQL)
Date d'analyse : 2026-09-28

---

## 0. Cartographie des tables (28 migrations → 22 modèles)

| # | Table | Modèle | Rôle | FK principales |
|---|-------|--------|------|-----------------|
| 1 | `users` | `User` | Comptes (client / producteur / admin) | — |
| 2 | `user_profiles` | `UserProfile` | Avatar, bio client | `user_id` |
| 3 | `producer_profiles` | `ProducerProfile` | Fiche ferme, coordonnées GPS | `user_id` |
| 4 | `producer_verifications` | `ProducerVerification` | Validation pièce d'identité | `producer_id` |
| 5 | `categories` | `Category` | Arborescence produits (auto-référence) | `parent_id` |
| 6 | `locations` | `Location` | Régions / villes | — |
| 7 | `products` | `Product` | Offres du producteur (slug unique) | `producer_id`, `category_id`, `location_id` |
| 8 | `product_images` | `ProductImage` | Galerie d'images | `product_id` |
| 9 | `product_reports` | `ProductReport` | Signalements (modération) | `product_id` |
| 10 | `carts` | `Cart` | Panier persistant, **1 par user** | `user_id` |
| 11 | `cart_items` | `CartItem` | Lignes de panier | `cart_id`, `product_id` |
| 12 | `orders` | `Order` | Commandes (status + `reference`) | `client_id` |
| 13 | `order_items` | `OrderItem` | Lignes figées (prix **snapshoté**) | `order_id` |
| 14 | `payments` | `Payment` | Paiements | `order_id` |
| 15 | `transactions` | `Transaction` | Journal financier | `payment_id` |
| 16 | `reviews` | `Review` | Avis produit, unique (product, client) | `product_id`, `client_id` |
| 17 | `producer_reviews` | `ProducerReview` | Avis producteur, unique (order, client) | `producer_id`, `order_id` |
| 18 | `conversations` | `Conversation` | Fil client↔producteur, unique (client, producer) | `client_id`, `producer_id` |
| 19 | `messages` | `Message` | Messages de discussion | `conversation_id` |
| 20 | `notifications` | `Notification` | Notifications in-app | `user_id` |
| 21 | `local_dishes` | `LocalDish` | Base de plats locaux (admin) | — |
| 22 | `local_dish_ingredients` | — | Ingrédients d'un plat | `local_dish_id`, `product_category_id` |
| — | `cache`, `jobs`, `sessions` | — | Infrastructure Laravel | — |

---

## PRIORITÉ 1 — Critique (monnaie, sécurité, intégrité transactionnelle)

### P1.1 — Création de commande (transaction critique)
`app/Services/OrderService.php::placeOrder()` — **verrou pessimiste `lockForUpdate()`**

```sql
START TRANSACTION;

-- 1. Panier du client (1 par user, index unique user_id)
SELECT * FROM carts WHERE user_id = ? LIMIT 1;

-- 2. Lignes de panier verrouillées
SELECT cart_items.*, products.*
FROM cart_items
INNER JOIN products ON products.id = cart_items.product_id
WHERE cart_items.cart_id = ?
ORDER BY cart_items.id
FOR UPDATE;

-- 3. Création de la commande
INSERT INTO orders
  (client_id, status, reference, payment_method, subtotal, shipping_fee, total,
   shipping_name, shipping_phone, shipping_address, placed_at, created_at, updated_at)
VALUES (?, 'preparing', ?, ?, 0, 1000, 0, ?, ?, ?, NOW(), NOW(), NOW());

-- 4. Snapshot du prix produit, ligne par ligne
SELECT * FROM products WHERE id = ? FOR UPDATE;
--    (price * quantity figés dans order_items, jamais relus ensuite)

-- 5. Insertion des lignes de commande
INSERT INTO order_items
  (order_id, product_id, product_name, unit, quantity, unit_price, line_total, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW());

-- 6. Mise à jour du total + décrément du stock
UPDATE orders SET subtotal = ?, total = ?, updated_at = NOW() WHERE id = ?;
UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?;

-- 7. Vidage du panier
DELETE FROM cart_items WHERE cart_id = ?;

COMMIT;
```

**Points critiques :**
- `SELECT ... FOR UPDATE` empêche la double-commande concurrente sur le même panier.
- Le client ne fournit **jamais** de prix : tout est recalculé côté serveur (anti-tampering).
- Prix **snapshoté** dans `order_items` → un changement de prix ultérieur ne réécrit pas l'historique.
- Référence de commande générée par `nextReference()`.

### P1.2 — Calcul du sous-total panier
`app/Models/Cart.php::subtotal()` — protection contre la modification du prix côté client

```sql
SELECT COALESCE(SUM(products.price * cart_items.quantity), 0) AS subtotal
FROM cart_items
INNER JOIN products ON products.id = cart_items.product_id
WHERE cart_items.cart_id = ?;
```

### P1.3 — Chiffrement des lignes
`app/Services/CartService.php` (via `CartController`)

```sql
-- Ajout
INSERT INTO cart_items (cart_id, product_id, quantity, created_at, updated_at)
VALUES (?, ?, ?, NOW(), NOW())
ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), updated_at = NOW();

-- Modification quantité
UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ? AND cart_id = ?;

-- Suppression
DELETE FROM cart_items WHERE id = ? AND cart_id = ?;

-- Vidage
DELETE FROM cart_items WHERE cart_id = ?;
```

### P1.4 — Authentification & unicité d'identité
`app/Http/Controllers/Auth/RegisterController.php` (inscription en 6 étapes)

```sql
-- Contrôle d'unicité email (étape 5)
SELECT EXISTS(SELECT 1 FROM users WHERE email = ?);

-- Contrôle d'unicité téléphone (étape 5)
SELECT EXISTS(SELECT 1 FROM users WHERE phone = ?);

-- Création compte + profil en transaction
INSERT INTO users (name, email, phone, password, role, region, gender, activity_type, ...) VALUES (...);
INSERT INTO producer_profiles (user_id, farm_name, ...) VALUES (...);
```

> `App\Models\User` : `password` est un champ `hashed` (bcrypt), jamais stocké en clair.

### P1.5 — Politiques d'accès (anti-IDOR, appliquées en SQL)
`app/Policies/ProductPolicy.php`, `CartPolicy.php`, `UserPolicy.php`, `ProducerReviewPolicy.php`

```sql
-- Un producteur ne voit/modifie QUE ses propres offres
SELECT * FROM products WHERE slug = ? AND producer_id = ?;   -- + Gate::authorize()

-- Un client ne voit QUE son panier
SELECT * FROM carts WHERE user_id = ?;

-- Un producteur ne voit QUE les commandes contenant SON produit
SELECT DISTINCT orders.*
FROM orders
INNER JOIN order_items ON order_items.order_id = orders.id
WHERE order_items.product_id IN (
    SELECT id FROM products WHERE producer_id = ?
)
ORDER BY orders.id DESC;
```

### P1.6 — Paiement & transaction financière
`app/Services/TransactionService.php`, `app/Http/Controllers/PaymentController.php`

```sql
INSERT INTO payments (order_id, amount, method, status, reference, created_at, updated_at) VALUES (...);
INSERT INTO transactions (payment_id, order_id, type, amount, status, created_at, updated_at) VALUES (...);
UPDATE payments SET status = 'paid', paid_at = NOW(), updated_at = NOW() WHERE id = ?;
UPDATE orders SET status = 'confirmed', updated_at = NOW() WHERE id = ?;
```

---

## PRIORITÉ 2 — Élevée (catalogue, recherche, marketplace)

### P2.1 — Liste du catalogue / marketplace
`app/Http/Controllers/ProductController.php`, `app/Models/Product.php`

```sql
-- Catalogue avec filtres catégorie / région / recherche plein texte
SELECT products.*, users.name AS producer_name, categories.name AS category_name
FROM products
INNER JOIN users ON users.id = products.producer_id
LEFT JOIN categories ON categories.id = products.category_id
WHERE products.status = 'published'
  AND products.is_available = 1
  AND products.stock_quantity > 0
  AND (:category IS NULL OR products.category_id = :category)
  AND (:region   IS NULL OR products.location_id = :region)
  AND (:search   IS NULL OR products.name LIKE CONCAT('%', :search, '%'))
ORDER BY FIELD(products.status, 'published'), products.created_at DESC
LIMIT 24 OFFSET 0;

-- Fiche produit
SELECT * FROM products WHERE slug = ? LIMIT 1;
SELECT * FROM product_images WHERE product_id = ? ORDER BY is_cover DESC, id;

-- Slug unique (collision gérée en PHP)
SELECT EXISTS(SELECT 1 FROM products WHERE slug = ?);
```

### P2.2 — Marché des prix (agrégats purs, aucun appel IA)
`app/Services/MarketPriceService.php::stats()`

```sql
SELECT products.name, products.unit,
       MIN(products.slug)                       AS slug,
       categories.name                          AS category,
       COUNT(*)                                 AS offers,
       ROUND(AVG(products.price), 2)            AS avg_price,
       MIN(products.price)                      AS min_price,
       MAX(products.price)                      AS max_price
FROM products
INNER JOIN users ON users.id = products.producer_id
LEFT JOIN categories ON categories.id = products.category_id
WHERE products.is_available = 1
  AND products.status = 'published'
  AND products.stock_quantity > 0
GROUP BY products.name, products.unit, categories.name
ORDER BY products.name;
```

### P2.3 — Création / mise à jour d'offre (producteur)
`app/Http/Controllers/DashboardController.php`, `app/Http/Requests/UpdateProductRequest.php`

```sql
-- Création
INSERT INTO products
  (producer_id, category_id, location_id, name, slug, description, price, unit,
   stock_quantity, minimum_order, is_available, status, featured_image, published_at, created_at, updated_at)
VALUES (:producer_id, :category_id, ..., NOW(), NOW(), NOW());

-- Mise à jour (IDOR-safe via Gate)
UPDATE products SET name = ?, price = ?, stock_quantity = ?, updated_at = NOW() WHERE slug = ?;

-- Bascule de disponibilité
UPDATE products SET is_available = NOT is_available, updated_at = NOW() WHERE id = ? AND producer_id = ?;

-- Suppression
DELETE FROM products WHERE slug = ?;   -- CASCADE : images, cart_items, reviews

-- Un avis par client et par produit (validation unique)
SELECT EXISTS(SELECT 1 FROM products WHERE slug = ? AND id <> ?);
```

---

## PRIORITÉ 3 — Moyenne (avis, notation, IA, messagerie)

### P3.1 — Avis produit + recalcul de note producteur
`app/Services/ReviewService.php`, `app/Http/Controllers/ReviewController.php`

```sql
-- Insertion d'un avis
INSERT INTO reviews (product_id, client_id, order_id, rating, title, comment,
                     is_verified_purchase, status, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, ?, 1, 'pending', NOW(), NOW());

-- Note moyenne d'un produit
SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS reviews_count
FROM reviews
WHERE product_id = ? AND status = 'published';

-- Score moyen d'un producteur (recalculé à chaque masquage d'avis)
SELECT ROUND(AVG(reviews.rating), 1) AS producer_rating
FROM reviews
INNER JOIN products ON products.id = reviews.product_id
WHERE products.producer_id = ? AND reviews.status = 'published';

-- Liste des avis
SELECT reviews.*, users.name AS client_name, products.name AS product_name
FROM reviews
INNER JOIN users ON users.id = reviews.client_id
INNER JOIN products ON products.id = reviews.product_id
WHERE products.slug = ? AND reviews.status = 'published'
ORDER BY reviews.created_at DESC;
```

### P3.2 — Avis producteur (transactionnel)
`app/Services/ProducerReviewService.php`

```sql
-- Avis unique par (order_id, client_id) → la transaction fait foi
SELECT EXISTS(SELECT 1 FROM producer_reviews WHERE order_id = ? AND client_id = ?);

INSERT INTO producer_reviews (producer_id, client_id, order_id, rating, comment, status, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, 'published', NOW(), NOW());

-- Un avis producteur par commande et par client
ALTER TABLE producer_reviews ADD CONSTRAINT chk_producer_reviews_rating
  CHECK (rating BETWEEN 1 AND 5);
```

### P3.3 — Recommandations IA (contextualisation SQL)
`app/Services/GeminiRecommendationService.php`

```sql
-- Contexte produit pour le prompt
SELECT products.id, products.name, products.price, products.unit,
       users.region,
       COALESCE(users.name, '')          AS producer,
       COALESCE(categories.name, "")      AS category,
       COALESCE(AVG(reviews.rating), 0)   AS rating
FROM products
INNER JOIN users ON users.id = products.producer_id
LEFT JOIN categories ON categories.id = products.category_id
LEFT JOIN reviews ON reviews.product_id = products.id AND reviews.status = 'published'
WHERE products.status = 'published' AND products.is_available = 1
GROUP BY products.id
LIMIT 50;

-- Top 15 produits les plus achetés (cohérence du panier)
SELECT order_items.product_name AS product,
       MAX(categories.name)     AS category,
       SUM(order_items.quantity) AS total_qty
FROM order_items
INNER JOIN products ON products.id = order_items.product_id
LEFT JOIN categories ON categories.id = products.category_id
GROUP BY order_items.product_name, categories.name
ORDER BY SUM(order_items.quantity) DESC
LIMIT 15;
```

### P3.4 — Assistant plats locaux
`app/Services/GeminiDishAssistantService.php`, `AdminController` (CRUD plats)

```sql
-- Ingrédients d'un plat (base de connaissance, saisie admin)
SELECT ldi.*, categories.name AS category_name
FROM local_dish_ingredients ldi
LEFT JOIN categories ON categories.id = ldi.product_category_id
WHERE ldi.local_dish_id = ?;

-- Correspondance ingrédient <-> produits disponibles
SELECT products.id, products.name, products.slug, products.price, products.unit,
       users.name AS producer,
       COALESCE(AVG(reviews.rating), 0) AS rating
FROM products
INNER JOIN users ON users.id = products.producer_id
LEFT JOIN reviews ON reviews.product_id = products.id AND reviews.status = 'published'
WHERE products.status = 'published' AND products.is_available = 1
GROUP BY products.id, users.name;

-- CRUD admin
INSERT INTO local_dishes (name, region, description, created_at, updated_at) VALUES (...);
UPDATE local_dishes SET name = ?, region = ?, description = ? WHERE id = ?;
DELETE FROM local_dishes WHERE id = ?;   -- CASCADE local_dish_ingredients

-- Recherche d'un plat
SELECT * FROM local_dishes WHERE name LIKE CONCAT('%', ?, '%');
```

### P3.5 — Messagerie client ↔ producteur
`app/Http/Controllers/MessagingController.php`

```sql
-- Trouver / créer la conversation (unique client_id + producer_id)
SELECT * FROM conversations WHERE client_id = ? AND producer_id = ?;

INSERT INTO conversations (client_id, producer_id, last_message_at, created_at, updated_at)
VALUES (?, ?, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE last_message_at = NOW();

-- Historique paginé
SELECT messages.*, users.name AS sender_name
FROM messages
INNER JOIN users ON users.id = messages.sender_id
WHERE messages.conversation_id = ?
ORDER BY messages.created_at ASC
LIMIT 50 OFFSET ?;

-- Envoi
INSERT INTO messages (conversation_id, sender_id, body, is_read, created_at, updated_at)
VALUES (?, ?, ?, 0, NOW(), NOW());
UPDATE conversations SET last_message_at = NOW() WHERE id = ?;

-- Non lus
SELECT COUNT(*) FROM messages WHERE conversation_id = ? AND is_read = 0;
```

### P3.6 — Notifications
`app/Notifications/*`, `Notification` model

```sql
SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20;
UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?;
```

---

## PRIORITÉ 4 — Basse (administration, modération, géolocalisation)

### P4.1 — Back-office admin
`app/Services/AdminService.php`

```sql
-- Utilisateurs (filtre rôle + recherche)
SELECT users.*, user_profiles.*, producer_profiles.*
FROM users
LEFT JOIN user_profiles ON user_profiles.user_id = users.id
LEFT JOIN producer_profiles ON producer_profiles.user_id = users.id
WHERE (:role = 'all' OR users.role = :role)
  AND (:search = '' OR users.name LIKE CONCAT('%', :search, '%')
                   OR users.email LIKE CONCAT('%', :search, '%'))
ORDER BY users.id DESC
LIMIT 20;

-- Modération produits : signalements en tête, puis suspendus, puis publiés
SELECT products.*, users.name AS producer_name,
       (SELECT COUNT(*) FROM product_reports
         WHERE product_reports.product_id = products.id
           AND product_reports.status = 'pending') AS pending_reports_count
FROM products
INNER JOIN users ON users.id = products.producer_id
WHERE (:search = '' OR products.name LIKE CONCAT('%', :search, '%')
                 OR users.name  LIKE CONCAT('%', :search, '%'))
ORDER BY pending_reports_count DESC,
         CASE products.status WHEN 'suspended' THEN 0 WHEN 'published' THEN 1 ELSE 2 END,
         products.id DESC
LIMIT 20;

-- Motifs de signalement en attente
SELECT pr.*, users.name AS reporter_name
FROM product_reports pr
INNER JOIN users ON users.id = pr.reporter_id
WHERE pr.product_id = ? AND pr.status = 'pending'
ORDER BY pr.created_at DESC;

-- Modération directe (effet réel en base)
UPDATE products SET status = 'suspended', is_available = 0, updated_at = NOW() WHERE id = ?;
UPDATE products SET status = 'published', is_available = 1, updated_at = NOW() WHERE id = ?;

-- Supervision des commandes
SELECT orders.*, users.name AS client_name, order_items.*
FROM orders
INNER JOIN users ON users.id = orders.client_id
LEFT JOIN order_items ON order_items.order_id = orders.id
WHERE (:status = 'all' OR orders.status = :status)
ORDER BY orders.id DESC
LIMIT 20;

-- Modération des avis : abusifs (note basse) en premier
SELECT reviews.*, users.name AS client_name, products.name AS product_name
FROM reviews
INNER JOIN users ON users.id = reviews.client_id
INNER JOIN products ON products.id = reviews.product_id
WHERE (:filter = 'all' OR reviews.status = :filter)
ORDER BY CASE reviews.status WHEN 'published' THEN 0 ELSE 1 END,
         reviews.rating ASC, reviews.id DESC
LIMIT 20;

UPDATE reviews SET status = 'hidden',    updated_at = NOW() WHERE id = ?;
UPDATE reviews SET status = 'published', updated_at = NOW() WHERE id = ?;

-- Top produits (fréquence d'offre)
SELECT name, unit, COUNT(*) AS offers_count,
       ROUND(AVG(price), 0) AS avg_price,
       ROUND(MIN(price), 0) AS min_price,
       ROUND(MAX(price), 0) AS max_price
FROM products
WHERE status = 'published'
GROUP BY name, unit
ORDER BY offers_count DESC
LIMIT 50;

-- Catégories avec compteur
SELECT categories.*, COUNT(products.id) AS products_count
FROM categories
LEFT JOIN products ON products.category_id = categories.id
GROUP BY categories.id
ORDER BY categories.name;
```

### P4.2 — Statistiques dashboard
`app/Http/Controllers/DashboardController.php`

```sql
-- Commandes du client
SELECT orders.*, order_items.*
FROM orders
LEFT JOIN order_items ON order_items.order_id = orders.id
WHERE orders.client_id = ?
ORDER BY orders.created_at DESC;

-- Chiffre d'affaires du producteur
SELECT SUM(order_items.line_total) AS revenue
FROM order_items
INNER JOIN products ON products.id = order_items.product_id
WHERE products.producer_id = ?;

-- Compteur panier (badge)
SELECT COUNT(*) FROM cart_items ci
INNER JOIN carts c ON c.id = ci.cart_id
WHERE c.user_id = ?;
```

### P4.3 — Géolocalisation
`app/Services/GeocodingService.php`, `GeoapifyService.php`, `GeocodeProducers` (commande artisan)

```sql
-- Producteurs proches d'un point (bounding box)
SELECT users.*, producer_profiles.latitude, producer_profiles.longitude
FROM users
INNER JOIN producer_profiles ON producer_profiles.user_id = users.id
WHERE users.role = 'producer'
  AND producer_profiles.latitude BETWEEN :min_lat AND :max_lat
  AND producer_profiles.longitude BETWEEN :min_lng AND :max_lng
ORDER BY producer_profiles.last_geocoded_at ASC
LIMIT 50;

-- Mise à jour coordonnées (batch artisan)
UPDATE producer_profiles SET latitude = ?, longitude = ?, last_geocoded_at = NOW() WHERE user_id = ?;
UPDATE users SET latitude = ?, longitude = ? WHERE id = ?;
```

---

## PRIORITÉ 5 — Infrastructure Laravel

```sql
-- Sessions
SELECT * FROM sessions WHERE id = ?;
DELETE FROM sessions WHERE last_activity < ?;

-- Cache (table)
SELECT * FROM cache WHERE key = ?;
INSERT INTO cache (key, value, expiration) VALUES (?, ?, ?);
DELETE FROM cache WHERE key = ?;

-- File d'attente
INSERT INTO jobs (queue, payload, attempts, available_at, created_at) VALUES (...);
SELECT * FROM jobs WHERE reserved_at IS NULL;
```

---

## Synthèse quantitative

| Priorité | Domaine | Tables concernées | Criticité |
|----------|---------|-------------------|-----------|
| **P1** | Commandes, panier, paiement, auth, IDOR | `orders`, `order_items`, `carts`, `cart_items`, `products`, `payments`, `transactions`, `users` | 🔴 Critique |
| **P2** | Catalogue, recherche, marché des prix | `products`, `categories`, `users`, `product_images` | 🟠 Élevée |
| **P3** | Avis, IA, plats locaux, messagerie | `reviews`, `producer_reviews`, `local_dishes`, `local_dish_ingredients`, `messages`, `conversations`, `notifications` | 🟡 Moyenne |
| **P4** | Admin, modération, géolocalisation | `product_reports`, `producer_profiles`, `users`, `locations` | 🟢 Basse |
| **P5** | Cache, sessions, jobs | `cache`, `sessions`, `jobs` | ⚪ Infra |

---

## Bonnes pratiques appliquées dans le projet ✅

| Pratique | Preuve |
|----------|--------|
| **Prix snapshotés** | `order_items.unit_price` figé à la création → historique immuable |
| **Anti-tampering** | Aucun prix/total issu de la requête HTTP, tout recalculé serveur |
| **Verrouillage pessimiste** | `lockForUpdate()` sur panier, lignes et produits |
| **Contrainte SQL** | `CHECK (rating BETWEEN 1 AND 5)` sur `reviews` et `producer_reviews` |
| **Unicité applicative** | `UNIQUE (product_id, client_id)`, `UNIQUE (order_id, client_id)`, `UNIQUE (user_id)` sur `carts` |
| **Anti-IDOR** | Toutes les lectures de commandes filtrées par `client_id` **ou** `products.producer_id` |
| **Cascade maîtrisée** | `cascadeOnDelete` sur les liens de possession, `nullOnDelete` sur les liens de référence |
| **Agrégats SQL** | Statistiques de marché calculées en base, pas en PHP |
| **Slug unique** | Génération + collision gérée en boucle avant `INSERT` |
| **Requêtes paramétrées** | Eloquent exclusivement, aucun SQL concaténé (`like` via bindings) |

---

## Points d'attention recommandés

1. **`products.name` utilisé comme clé de groupement** dans `MarketPriceService` et `priceMarket()` — deux produits homonymes (ex. « Riz ») fusionnent leurs statistiques, y compris s'ils n'ont pas la même unité responsable de l'agrégation. Envisager un `products.category_id` + `unit` comme clé composée normalisée.
2. **`withCount` + `orderByDesc` sur Products** (`AdminService::products`) — la sous-requête de comptage s'exécute sur les 20 lignes paginées, ce qui est correct, mais le `LIKE '%...%'` sur `products.name` et `users.name` reste non indexable en B-Tree. Prévoir un index FULLTEXT si le catalogue grossit.
3. **`local_dish_ingredients` n'a pas de modèle Eloquent dédié** — le CRUD admin passe par le contrôleur. Ajouter `LocalDishIngredient` alignerait la couche données.
4. **`CHECK` constraints** — ignorés silencieusement par MySQL avant 8.0.16. Vérifier la version du serveur pour que la garantie de notation soit effective.
5. **Index manquants probables** : `order_items(product_id)`, `messages(conversation_id, is_read)`, `products(status, is_available, category_id)` pour les listes marketplace filtrées.
