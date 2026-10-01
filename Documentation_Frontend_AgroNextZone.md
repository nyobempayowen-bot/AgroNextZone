# Documentation Frontend AgroNextZone

> Analyse statique le 1 octobre 2026. Frontend Blade rendu serveur; aucun framework SPA identifié.

## 1. Architecture Frontend

Les 40 `resources/views/**/*.blade.php` composent l'interface. Vite compile `resources/css/app.css` et `resources/js/app.js`; le JavaScript métier est principalement inclus directement dans les vues.

## 2. Layouts

`resources/views/components/sidebar.blade.php` est la navigation transverse. `resources/views/admin/layout.blade.php` est le layout admin. Aucun layout global `@extends` principal n'a été trouvé: la structure est répliquée entre pages.

## 3. Pages et 4. Vues Blade

| Page/fonctionnalité | Vue Blade | Layout/composants | JS/CSS | Route |
|---|---|---|---|---|
| Accueil/marketplace | `Accueil.blade.php` | sidebar, product-card/modal, ai-bubble | inline + Vite | home, marketplace |
| Détail produit | `produit.blade.php` | sidebar, review-form, star-rating | inline | produit |
| Connexion/inscription | `auth/login.blade.php`, `auth/register.blade.php` | autonome | multi-étapes/géo inline | login, register* |
| Panier/checkout/paiement | `panier.blade.php`, `checkout.blade.php`, `payment.blade.php` | sidebar | formulaires Blade | panier, checkout*, payment.* |
| Profil | `profil.blade.php` | sidebar, producer-rating-form | inline | profil |
| Dashboards | `dashboard/client.blade.php`, `producer.blade.php`, `admin.blade.php` | sidebar | inline | dashboards |
| Messagerie | `messagerie.blade.php`, `discussion.blade.php` | sidebar | inline | messagerie, discussion* |
| IA/prix | `assistant-repas.blade.php`, `market-prix.blade.php` | sidebar, ai-bubble | fetch assistant | assistant-repas*, marche.prix |
| Administration | `admin/*.blade.php` (10) | admin/layout | formulaires/IA inline | admin.* |
| Institutionnel | `pages/a-propos.blade.php`, `pages/confidentialite.blade.php` | autonome | CSS commun | a-propos, confidentialite |

## 5. Navigation et 6. Sidebar

La sidebar affiche, selon le rôle: visiteur (accueil, marketplace, inscription, connexion); client (panier, assistant, commandes, messagerie, compte); producteur (offres, commandes, transactions, notifications, fiabilité, compte); admin (utilisateurs, vérifications, catalogue, commandes, avis, prix, plats, réglages). Fichier: `resources/views/components/sidebar.blade.php`.

## 7. Marketplace et 8. Produits

`Accueil.blade.php` consomme ProductController@index; `components/product-card.blade.php` et `product-modal.blade.php` affichent les offres/actions. `produit.blade.php` détaille le produit. Recherche/filtres GET: **EXISTANTE**. Édition/activation/suppression sont intégrées au dashboard producteur, pas des pages dédiées.

## 9. Panier, 10. Commandes, 11. Paiement

Panier: `panier.blade.php`; livraison/moyen: `checkout.blade.php`; décision: `payment.blade.php`. Les commandes apparaissent dans les onglets de dashboard. Paiement **PARTIELLE**: simulation confirmée dans les routes/contrôleur.

## 12. Avis et 13. Notation

`review-form.blade.php`, `quick-product-rating-form.blade.php`, `star-rating.blade.php` sont les avis produit. `producer-rating-form.blade.php` note le producteur. `post-purchase-invitation.blade.php` invite après achat confirmé; l'éligibilité vient du backend.

## 14. Messagerie et 15. Notifications

`messagerie.blade.php` liste les conversations; `discussion.blade.php` affiche/envoie/édite. Notifications dans les onglets dashboards. Pas de centre indépendant ni push temps réel repéré: **PARTIELLE**.

## 16. Profil

`profil.blade.php` est le profil producteur public. Les comptes sont les onglets `account` de `dashboard/client.blade.php` et `dashboard/producer.blade.php`; `/mon-profil` y redirige.

## 17. Géolocalisation

`auth/register.blade.php` appelle les APIs serveur `/geolocation/reverse` et `/geolocation/search`; aucune clé ne doit être côté client. Exécution/configuration **À VÉRIFIER**.

## 18. IA

`assistant-repas.blade.php` utilise AJAX pour assistant/historique/suggestions. `components/ai-bubble.blade.php` sert le catalogue/recommandations. `admin/dishes.blade.php` contient l'extraction IA. Service externe OpenRouter: **À VÉRIFIER**.

## 19. Dashboard Client

`dashboard/client.blade.php`: commandes, invitations avis, notifications, compte; route client uniquement.

## 20. Dashboard Producteur

`dashboard/producer.blade.php`: aperçu, offres, commandes propres, transactions/retraits, notifications, fiabilité, compte. **Un producteur ne peut pas acheter**: panier/checkout/paiement sont `role:client` aussi côté backend.

## 21. Dashboard Admin

`dashboard/admin.blade.php` est la synthèse; écrans `admin/users`, `user_show`, `verifications`, `reports`, `products`, `orders`, `reviews`, `prices`, `dishes`, `settings`; tous admin.

## 22. JavaScript

`resources/js/app.js` est vide. Les scripts des pages (sidebar, inscription, assistant, cartes, avis, dashboards/admin) portent le comportement. Aucune bibliothèque JS applicative supplémentaire dans `package.json`.

## 23. CSS

`resources/css/app.css` importe Tailwind, déclare les sources, gère sidebar et responsive. `public/style.css` existe mais aucune référence active confirmée: **À VÉRIFIER / potentiellement obsolète**. Images statiques: `public/images/`.

## 24. Responsive

Tailwind domine; `app.css` ajuste `--sidebar-width` sous 767px. Validation visuelle réelle **À VÉRIFIER**: navigateur non exécuté.

## 25. Parcours utilisateurs

| Rôle | Parcours réellement routé |
|---|---|
| Visiteur | Accueil/Marketplace → produit → connexion/inscription; profil producteur, prix et pages institutionnelles |
| Client | Connexion → dashboard → marketplace → produit → panier → checkout → paiement simulé → commandes → avis; messagerie/assistant/compte |
| Producteur | Connexion → dashboard → offres → commandes concernées → statut → transactions/retrait; messagerie/compte; sans achat |
| Administrateur | Connexion → dashboard → utilisateurs/vérifications → catalogue/signalements → commandes/avis/prix → plats/catégories |

## 26. Points importants à connaître

### RÉFÉRENCE RAPIDE

| Je veux modifier… | Fichier principal à consulter |
|---|---|
| Sidebar/navigation | `resources/views/components/sidebar.blade.php` |
| Accueil/marketplace | `resources/views/Accueil.blade.php` |
| Carte produit | `resources/views/components/product-card.blade.php` |
| Détail/avis | `resources/views/produit.blade.php` |
| Panier | `resources/views/panier.blade.php` |
| Checkout/paiement | `resources/views/checkout.blade.php`, `resources/views/payment.blade.php` |
| Dashboards | `resources/views/dashboard/` |
| Messagerie | `resources/views/messagerie.blade.php`, `discussion.blade.php` |
| IA | `resources/views/assistant-repas.blade.php` |
| Admin | `resources/views/admin/layout.blade.php`, `resources/views/admin/` |
| CSS | `resources/css/app.css` |

### Éléments potentiellement obsolètes / ambiguïtés

- **POSSIBLE DUPLICATION :** `resources/views/marketplace.blade.php` existe mais `/marketplace` rend `Accueil.blade.php`.
- **POSSIBLE DUPLICATION :** `resources/views/welcome.blade.php` existe mais `/` rend `Accueil.blade.php`.
- `AuthController::showRegister()` rend `auth/register`, mais aucune route GET ne lui est affectée; RegisterController le fait.
- `public/style.css` n'est pas référencé de façon confirmée; le build actif est `resources/css/app.css` via Vite.
- Scripts inline dans sidebar, inscription, avis, assistant et dashboards: maintenance dispersée, pas une duplication fonctionnelle certaine.
- Aucune page/route dédiée repérée pour rapport public produit, recherche IA indépendante de l'assistant, confirmation de livraison distincte ou paiement réel: **PARTIELLE** ou **NON IMPLÉMENTÉE** comme parcours séparé.