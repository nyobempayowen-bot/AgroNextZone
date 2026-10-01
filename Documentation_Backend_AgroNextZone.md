# Documentation Backend AgroNextZone

> Cartographie statique au 1 octobre 2026. Aucune migration, requête SQL, test ni modification de logique n'a été exécuté.

## 1. Architecture générale

Monolithe Laravel 13 / PHP 8.3 : Blade rend le frontend, Vite/Tailwind compile les assets, `routes/web.php` concentre le HTTP et Eloquent persiste le métier. Il n'y a pas de `routes/api.php`. Les intégrations sont Geoapify/Géocodage et OpenRouter (classes au nom historique `Gemini…`).

| Partie | Emplacement | Rôle | Fonctionnalités principales |
|---|---|---|---|
| Backend Laravel | `app/`, `bootstrap/app.php` | cycle HTTP/Eloquent | logique métier |
| Frontend Blade | `resources/views/` | rendu serveur | pages et dashboards |
| Routes | `routes/web.php` | 50 déclarations | public, client, producteur, admin |
| Controllers | `app/Http/Controllers/` | orchestration | 14 fichiers |
| Models | `app/Models/` | données/relations | 21 fichiers, 22 classes |
| Services | `app/Services/` | règles/intégrations | 19 services |
| Middleware/Policies | `app/Http/Middleware/`, `app/Policies/` | contrôle d'accès | rôles, IDOR |
| Migrations/seeders | `database/` | schéma/init | 29 migrations |
| Storage/assets | `storage/`, `public/` | uploads/assets | images/documents |
| Tests/config | `tests/`, `config/` | vérification/config | 27 Feature, 1 Unit |

## 2. Structure des dossiers

`app/Http/Requests` valide les formulaires; `app/Services` porte la logique réutilisable. `resources/css/app.css` et `resources/js/app.js` sont les entrées Vite. `database/factories` et `database/seeders` servent aux données de test/init. Le contenu réel des uploads dans `storage` est **À VÉRIFIER**.

## 3. Routes

| Domaine | URLs / noms | Responsable / protection |
|---|---|---|
| Catalogue | `/`, `/marketplace`, `/produit/{slug}` | ProductController, public |
| Auth | `/login`, `/logout`, `/register` + étapes | AuthController, RegisterController |
| Panier/checkout | `/panier*`, `/checkout`, `/mes-commandes` | CartController/closures, client |
| Paiement | `/paiement/{reference}[/{outcome}]` | PaymentController, client |
| Client/producteur | `/client/*`, `/producer/*` | DashboardController, rôle dédié |
| Admin | `/admin/*` | AdminController, admin |
| Messagerie | `/messagerie`, `/discussion/{id}`, `/message/{message}` | MessagingController, client/producteur |
| Géo/IA | `/geolocation/*`, `/marche-prix`, `/recommandations-ia`, `/assistant-repas*` | Geo/Market/Dish controllers |
| Public | `/profil/{id}`, `/a-propos`, `/confidentialite` | closure/vues |

`routes/console.php` ne contient que la commande exemple `inspire`.

## 4. Controllers

| Controller | Emplacement | Responsabilités | Routes |
|---|---|---|---|
| ProductController | `app/Http/Controllers/ProductController.php` | catalogue, filtres, détail, achat immédiat | home, marketplace, produit |
| AuthController | `app/Http/Controllers/AuthController.php` | login/logout, inscription monobloc héritée | login, logout, register.store |
| Auth\RegisterController | `app/Http/Controllers/Auth/RegisterController.php` | inscription étapes 1–6, OTP, profils | register* |
| CartController | `app/Http/Controllers/CartController.php` | panier persistant/import legacy | panier* |
| DashboardController | `app/Http/Controllers/DashboardController.php` | portails, offres, commandes, comptes | client.*, producer.* |
| PaymentController | `app/Http/Controllers/PaymentController.php` | paiement simulé/transactions | payment.* |
| ReviewController / ProducerReviewController | `app/Http/Controllers/ReviewController.php`, `ProducerReviewController.php` | avis produit/note producteur | avis* |
| MessagingController | `app/Http/Controllers/MessagingController.php` | conversations/messages | messagerie, discussion* |
| GeoController | `app/Http/Controllers/GeoController.php` | Geoapify côté serveur | geolocation.* |
| MarketIntelligenceController | `app/Http/Controllers/MarketIntelligenceController.php` | prix/recommandations | marche.prix, recommandations.ia |
| DishAssistantController | `app/Http/Controllers/DishAssistantController.php` | assistant/historique | assistant-repas* |
| AdminController | `app/Http/Controllers/AdminController.php` | administration/modération/plats | admin.* |

## 5. Services

| Service | Emplacement | Fonction | Appelé par |
|---|---|---|---|
| CartService / OrderService | `app/Services/CartService.php`, `OrderService.php` | panier MySQL, commande, stock/statuts | CartController, checkout, dashboard |
| PaymentService / TransactionService | `app/Services/PaymentService.php`, `TransactionService.php` | paiement simulé, ventes/retraits | PaymentController, dashboard |
| ReviewService / ProducerReviewService / PostPurchaseReviewService | `app/Services/*ReviewService.php` | avis, scores, invitations | avis, paiement, dashboards |
| MessagingService / NotificationService | `app/Services/MessagingService.php`, `NotificationService.php` | messages et notifications DB | messaging, payment, admin |
| AdminService | `app/Services/AdminService.php` | agrégats, modération, catégories | AdminController |
| GeoapifyService / GeocodingService | `app/Services/GeoapifyService.php`, `GeocodingService.php` | recherche/géocodage/cache | GeoController, dashboard, commande |
| MarketPriceService | `app/Services/MarketPriceService.php` | statistiques prix locales | Market/admin |
| OpenRouterService | `app/Services/OpenRouterService.php` | HTTP/JSON IA | IA, admin |
| GeminiRecommendationService / GeminiDishAssistantService | `app/Services/Gemini*.php` | recommandations/assistant avec repli | contrôleurs IA |
| CatalogSearchService | `app/Services/CatalogSearchService.php` | synonymes/recherche ingrédients | assistant |
| RegisterProgress / ProductControllerShape | `app/Services/RegisterProgress.php`, `ProductControllerShape.php` | étapes inscription / adaptateur legacy | RegisterController/catalogue |

## 6. Models Eloquent

| Model | Table | Relations principales | Fonction |
|---|---|---|---|
| User | users | profils, produits, commandes, panier, conversations, avis | identité/rôle |
| UserProfile, ProducerProfile, ProducerVerification | tables homonymes | belongsTo User | informations/contrôle producteur |
| Category, Location | categories, locations | produits; parent/enfants | taxonomie/localisation |
| Product, ProductImage, ProductReport | products, product_images, product_reports | producteur/catégorie/lieu/images/avis | catalogue/modération |
| Cart, CartItem | carts, cart_items | utilisateur/items/produit | panier |
| Order, OrderItem, Payment, Transaction | tables homonymes | client/items/paiements/ventes | commande/revenus |
| Review, ProducerReview | reviews, producer_reviews | produit ou producteur/client/commande | notations |
| Conversation, Message | conversations, messages | client/producteur/messages/auteur | messagerie |
| Notification | notifications | DatabaseNotification polymorphe | alertes |
| LocalDish + LocalDishIngredient | local_dishes, local_dish_ingredients | plat → ingrédients/catégorie | assistant |

## 7. Relations

`OrderItem` relie commande–produit–producteur; `CartItem` relie panier–produit. `Product` a images, avis publiés et signalements. `Order` a items, paiements, avis et transactions. `Conversation` relie un client et un producteur avec unicité composite.

## 8. Migrations

Les 29 migrations créent/étendent l'infrastructure Laravel, rôles/profils, catégories/lieux, catalogue/images, commandes/lignes/paiements/transactions, avis, messagerie, notifications, vérifications, panier, coordonnées, signalements, plats locaux et notation producteur. Dernière : `2026_09_29_075722_add_edited_at_to_messages_table.php`.

## 9. Base de données

Connexion, données et état de migrations **À VÉRIFIER** : aucun accès DB. **PROBLÈME IDENTIFIÉ potentiel :** `Notification` étend `DatabaseNotification` et attend les colonnes polymorphes Laravel; la migration `create_notifications_table` visible ne les montre pas.

## 10. Middleware

`EnsureUserHasRole` est enregistré sous `role` par `AppServiceProvider`: connexion, rôle autorisé et compte non suspendu. `auth`/`throttle` sont appliqués par routes Laravel.

## 11. Policies

`AppServiceProvider` enregistre Cart, Conversation, Message, Order, Product, Review et User policies. `ProducerReviewPolicy.php` existe mais n'est pas enregistré explicitement; découverte conventionnelle **À VÉRIFIER**. Elles limitent les ressources au propriétaire, participant ou auteur.

## 12. Authentification

`AuthController` utilise l'auth Laravel. `GET /register` passe par `Auth\RegisterController`: session, upload, OTP puis création User/Profile/Verification. `POST /register` reste une voie monobloc legacy.

## 13. Autorisations

Visiteur: catalogue/profil/prix/géo inscription. Client: achat, panier, checkout, paiement, IA, avis. Producteur: ses offres/commandes/retraits, sans achat. Admin: `/admin/*`.

## 14. Panier

`CartController` + `CartService` utilisent `carts/cart_items`, recalculent prix/stock depuis `products`, et sont `role:client`. **POSSIBLE DUPLICATION/héritage:** closures `routes/web.php` et contrôleur traitent tous deux l'ancien panier session; les routes actives passent au contrôleur.

## 15. Commandes

`POST /checkout` valide `CheckoutRequest`, appelle `OrderService::placeOrder`, vide les clés session héritées, puis redirige paiement. Le service prend des instantanés de prix/lignes; le producteur ne change que les commandes contenant ses lignes.

## 16. Stock

`Product.stock_quantity`, disponibilité et minimum sont gérés par CartService/OrderService et DashboardController, avec vérification transactionnelle. Test dédié : `tests/Feature/StockTransactionTest.php`.

## 17. Paiement

`PaymentController` + `PaymentService`: paiement explicitement **simulé**, pas de passerelle réelle. En succès, notifications, transactions de vente et invitations d'avis sont créées.

## 18. Avis

`ReviewController` + `ReviewService`: client ayant acheté, note 1–5, une fois par produit; ReviewPolicy protège édition/suppression.

## 19. Notation producteur

`ProducerReviewController` + service: note après paiement/livraison. L'unicité `producer_reviews(order_id, client_id)` limite une note par commande/client même en présence de plusieurs producteurs : limitation métier **À VÉRIFIER**.

## 20. Messagerie

MessagingController/Service et Conversation/Message: client/producteur uniquement; participation et auteur contrôlés.

## 21. Notifications

NotificationService sert les alertes paiement, message, admin et dashboards. Voir incohérence de schéma possible (section 9).

## 22. Géolocalisation

GeoController appelle Geoapify côté serveur (`throttle:30,1` reverse; `20,1` search). La commande `geocode:producers` peut écrire des coordonnées; elle n'a pas été lancée.

## 23. IA

Recommandations: MarketIntelligenceController → GeminiRecommendationService → OpenRouter avec repli local. Assistant: DishAssistantController → GeminiDishAssistantService/CatalogSearchService → OpenRouter si configuré. Les clés/configurations sont **À VÉRIFIER**.

## 24. Sécurité

FormRequests, CSRF, rôles, policies, achat vérifié et chiffrement des pièces de vérification sont présents. À surveiller: `/register` legacy, Notification, l'autoload ingrédient, issue de paiement dans l'URL de simulation.

## 25. Tests

`tests/Feature` couvre auth, panier, checkout, IA, géo, marketplace, messagerie, avis, profil, stock et admin. `tests/Unit/ExampleTest.php` est le test unité exemple. Tests non exécutés.

## 26. Points importants à connaître

| Fonctionnalité | URL/route | Contrôleur/service | Modèles | Vue | Rôle/état |
|---|---|---|---|---|---|
| Inscription/connexion | `/register`, `/login` | Register/Auth + Progress | User/profils | auth/register/login | public, EXISTANTE |
| Catalogue/recherche/filtres | `/`, `/marketplace` | ProductController | Product/Category/Location | Accueil | public, EXISTANTE |
| Produit/images | `/produit/{slug}` | ProductController | Product/Image/Review | produit | public, EXISTANTE |
| Panier/commande | `/panier`, `/checkout` | Cart/Order | Cart*, Order* | panier/checkout | client, EXISTANTE |
| Paiement | `/paiement/{reference}` | Payment/Transaction | Payment/Transaction | payment | client, PARTIELLE (simulation) |
| Avis/notation | produit/profil | Review services | Review/ProducerReview | composants/profil | client, EXISTANTE |
| Messagerie | `/messagerie` | MessagingService | Conversation/Message | messagerie/discussion | client/producteur, EXISTANTE |
| Géo | `/geolocation/*` | Geoapify/Geocoding | Location/Profile | auth/register | À VÉRIFIER |
| IA | assistant/recommandations | Gemini*/OpenRouter | LocalDish/Product | assistant-repas | client, À VÉRIFIER |
| Admin | `/admin/*` | AdminService | données métier | admin/* | admin, EXISTANTE |

### Flux Backend / Frontend

| Flux | Chemin réel |
|---|---|
| Inscription | Blade register → routes step* → RegisterController/Progress → session → User/Profile → JSON/Blade |
| Connexion | Blade login → AuthController/Auth Laravel → session → dashboard |
| Ajout produit | dashboard → route producer.products.add → DashboardController → Product/Image → MySQL |
| Recherche | Accueil → marketplace → ProductController → Product/Category/Location → Blade |
| Panier | carte → panier.ajouter → CartController → CartService → Cart/Item/Product |
| Commande/paiement | checkout → OrderService → Order/Item/stock → PaymentController → Payment/Transaction/Notification |
| Avis | composant → ReviewController → ReviewService → Order/Payment/Review |
| Messagerie | discussion → MessagingController → MessagingService → Conversation/Message/Notification |
| IA | assistant → DishAssistantController → GeminiDishAssistant/CatalogSearch/OpenRouter → JSON |

### RÉFÉRENCE RAPIDE

| Je veux modifier… | Fichier principal à consulter |
|---|---|
| Produits/catalogue | `app/Http/Controllers/ProductController.php` |
| Commandes/stock | `app/Services/OrderService.php` |
| Panier | `app/Services/CartService.php` |
| Paiement | `app/Http/Controllers/PaymentController.php` |
| Avis | `app/Services/ReviewService.php` |
| Messagerie | `app/Services/MessagingService.php` |
| Profil | `app/Http/Controllers/DashboardController.php` |
| IA | `app/Services/GeminiDishAssistantService.php` |
| Administration | `app/Http/Controllers/AdminController.php` |

### Éléments potentiellement obsolètes / ambiguïtés

- **POSSIBLE DUPLICATION :** `POST /register`/AuthController contre l'inscription UI multi-étapes/RegisterController.
- **POSSIBLE DUPLICATION :** `DashboardController::adminDashboard()` existe, les routes admin utilisent `AdminController::dashboard()`.
- `ProductController::getDefaultProducts()`/ProductControllerShape et ProductSeeder maintiennent une forme legacy; panier/commandes gardent des clés session pour import/nettoyage.
- **PROBLÈME IDENTIFIÉ :** `LocalDishIngredient` est déclaré dans `app/Models/LocalDish.php`, sans fichier PSR-4; son autoload direct peut échouer.