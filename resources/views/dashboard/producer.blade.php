<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Espace Producteur | AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="min-h-full bg-slate-50 text-slate-800 antialiased flex flex-col justify-between">

    @include('components.sidebar')

    <!-- Top Producer Navbar -->
    <header class="sticky top-0 z-30 border-b border-emerald-900/10 bg-emerald-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <!-- Hamburger + Brand & Producer Identity -->
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="p-2 rounded-lg hover:bg-emerald-800 transition text-white" aria-label="Ouvrir le menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                        <div class="h-10 w-10 rounded-xl bg-emerald-700 text-white flex items-center justify-center font-black text-base shadow-sm ring-1 ring-emerald-500/50 group-hover:scale-105 transition">
                            AN
                        </div>
                        <div class="flex flex-col">
                            <span class="text-base font-bold text-white leading-tight">AgroNextZone</span>
                            <span class="text-[10px] font-semibold text-emerald-300 uppercase tracking-widest">Portail Producteur</span>
                        </div>
                    </a>
                </div>

                <!-- Fast Actions & User Menu -->
                <div class="flex items-center gap-3 sm:gap-5">
                    <!-- Quick Wallet Badge -->
                    <div class="hidden sm:flex items-center gap-2 bg-emerald-800/80 px-3.5 py-1.5 rounded-xl border border-emerald-700/60">
                        <span class="text-xs text-emerald-200">Solde disponible :</span>
                        <span class="text-sm font-black text-amber-300">{{ number_format($solde, 0, ',', ' ') }} FCFA</span>
                    </div>

                    <!-- Notification Button -->
                    <a href="{{ route('producer.dashboard', ['tab' => 'notifications']) }}" class="relative p-2 rounded-xl bg-emerald-800/70 hover:bg-emerald-800 text-emerald-200 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @php $unreadCount = collect($notifications)->where('lu', false)->count(); @endphp
                        @if ($unreadCount > 0)
                            <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-500 text-[10px] font-bold text-slate-900">{{ $unreadCount }}</span>
                        @endif
                    </a>

                    <!-- Link to Marketplace (read-only view) -->
                    <a href="{{ route('home') }}" class="text-xs font-semibold text-emerald-200 hover:text-white px-3 py-2 rounded-xl hover:bg-emerald-800 transition hidden md:inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Consulter le marché
                    </a>

                    <!-- Logout Button -->
                    <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                        @csrf
                        <button type="submit" class="text-xs font-semibold bg-emerald-800/80 hover:bg-rose-600 px-3.5 py-2 rounded-xl text-white transition">
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>

            </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">

        <!-- Flash Success / Error Messages -->
        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800 shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Pending CNI Verification Warning Banner -->
        @if (!($user->is_verified ?? false))
            <div class="mb-8 rounded-2xl border border-amber-200 bg-amber-50/90 p-4 sm:p-5 text-amber-900 shadow-sm flex items-start gap-3.5">
                <div class="h-10 w-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-sm text-amber-950">Vérification de votre compte Producteur en cours</h4>
                    <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                        Votre pièce d'identité (CNI) a bien été reçue et est actuellement examinée par notre équipe. Vous pouvez déjà gérer vos récoltes et configurer votre boutique. Le badge "Producteur Vérifié" sera activé dès validation.
                    </p>
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- TAB 1 : VUE D'ENSEMBLE                                                    -->
        <!-- ========================================================================= -->
        @if ($activeTab === 'overview')
            <div class="space-y-8">
                <!-- Welcome header & Producer Score Hero -->
                <div class="overflow-hidden rounded-3xl bg-emerald-950 p-6 text-white shadow-lg shadow-emerald-950/10 sm:p-8">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-300">Espace producteur · {{ $user->region ?? 'Région non renseignée' }}</p>
                        <h1 class="mt-3 text-2xl font-black sm:text-3xl">Bonjour, {{ $user->name }}</h1>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-emerald-100">Voici un aperçu de votre activité sur AgroNextZone.</p>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @php
                        $overviewCards = [
                            ['label' => 'Ventes enregistrées', 'value' => $dashboardStats['revenue'] > 0 ? number_format($dashboardStats['revenue'], 0, ',', ' ') . ' FCFA' : null, 'hint' => 'À partir des commandes reçues', 'tone' => 'emerald'],
                            ['label' => 'Commandes reçues', 'value' => $dashboardStats['orders_count'], 'hint' => $dashboardStats['pending_orders'] . ' en attente ou préparation', 'tone' => 'sky'],
                            ['label' => 'Produits publiés', 'value' => $dashboardStats['products_count'], 'hint' => $dashboardStats['available_products'] . ' actuellement en stock', 'tone' => 'amber'],
                            ['label' => 'Commandes terminées', 'value' => $dashboardStats['completed_orders'], 'hint' => 'Statut Livrée', 'tone' => 'lime'],
                        ];
                    @endphp
                    @foreach ($overviewCards as $card)
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold text-slate-500">{{ $card['label'] }}</p>
                            @if ($card['value'] !== null)
                                <p class="mt-3 text-2xl font-black text-slate-900">{{ $card['value'] }}</p>
                                <p class="mt-2 text-xs text-slate-500">{{ $card['hint'] }}</p>
                            @else
                                <p class="mt-3 text-sm font-semibold text-slate-400">Aucune donnée disponible pour le moment.</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div><h2 class="text-base font-bold text-slate-900">Score et évaluations</h2><p class="mt-1 text-xs text-slate-500">Calculés à partir de vos produits associés.</p></div>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">{{ $dashboardStats['reviews_count'] }} avis</span>
                        </div>
                        @if ($dashboardStats['average_score'] !== null)
                            <div class="mt-5 flex items-end gap-3"><span class="text-4xl font-black text-emerald-700">{{ number_format($dashboardStats['average_score'], 1, ',', ' ') }}</span><span class="pb-1 text-sm text-slate-400">/ 5</span><span class="pb-1 text-xs font-semibold text-slate-500">Note moyenne</span></div>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-600" style="width: {{ min(100, $dashboardStats['average_score'] * 20) }}%"></div></div>
                        @else
                            <p class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Aucune donnée disponible pour le moment.</p>
                        @endif
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-base font-bold text-slate-900">Activité récente</h2>
                        @forelse (array_slice($notifications, 0, 3) as $notification)
                            <div class="border-b border-slate-100 py-3 last:border-0"><p class="text-sm font-semibold text-slate-800">{{ $notification['titre'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $notification['temps'] }}</p></div>
                        @empty
                            <p class="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Aucune donnée disponible pour le moment.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Orders Table -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-5 sm:px-6 flex items-center justify-between border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Dernières commandes clients</h2>
                            <p class="text-xs text-slate-500">Commandes à préparer et expédier</p>
                        </div>
                        <a href="{{ route('producer.dashboard', ['tab' => 'orders']) }}" class="text-xs font-bold text-emerald-700 hover:underline">
                            Voir toutes les commandes →
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-left text-xs sm:text-sm">
                            <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3.5">Réf. Commande</th>
                                    <th class="px-5 py-3.5">Client</th>
                                    <th class="px-5 py-3.5">Produit & Quantité</th>
                                    <th class="px-5 py-3.5">Montant</th>
                                    <th class="px-5 py-3.5">Statut</th>
                                    <th class="px-5 py-3.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse (array_slice($orders, 0, 3) as $ord)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-5 py-4 font-mono font-bold text-emerald-800">{{ $ord['id'] }}</td>
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-slate-900">{{ $ord['client_nom'] }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $ord['client_phone'] }}</div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="text-slate-800 font-medium">{{ $ord['produit'] }}</div>
                                            <div class="text-[11px] text-slate-500">{{ $ord['quantite'] }}</div>
                                        </td>
                                        <td class="px-5 py-4 font-bold text-slate-900">{{ $ord['montant'] }}</td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $ord['statut'] === 'Livrée' ? 'bg-emerald-100 text-emerald-800' : ($ord['statut'] === 'Expédiée' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') }}">
                                                {{ $ord['statut'] }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <a href="{{ route('producer.dashboard', ['tab' => 'orders']) }}" class="text-xs font-bold text-emerald-700 hover:underline">Gérer</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Aucune donnée disponible pour le moment.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div><h2 class="text-base font-bold text-slate-900">Produits les plus récents</h2><p class="mt-1 text-xs text-slate-500">Vos offres publiées sur la marketplace.</p></div>
                        <a href="{{ route('producer.dashboard', ['tab' => 'products']) }}" class="text-xs font-bold text-emerald-700 hover:underline">Gérer les produits</a>
                    </div>
                    @forelse (array_slice($products, 0, 4) as $product)
                        <div class="flex items-center gap-3 border-b border-slate-100 py-4 last:border-0">
                            <img src="{{ $product['image'] }}" alt="{{ $product['nom'] }}" class="h-12 w-12 rounded-xl object-cover" />
                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-bold text-slate-800">{{ $product['nom'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $product['prix'] }} · Stock {{ $product['stock'] ?? '—' }}</p></div>
                            <span class="rounded-full {{ ($product['statut'] ?? '') === 'En stock' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-[10px] font-bold">{{ $product['statut'] ?? '—' }}</span>
                        </div>
                    @empty
                        <p class="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Aucune donnée disponible pour le moment.</p>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- TAB 2 : GESTION DES OFFRES & PRODUITS                                     -->
        <!-- ========================================================================= -->
        @if ($activeTab === 'products')
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">Mes Offres & Produits en vente</h1>
                        <p class="text-xs sm:text-sm text-slate-500">Ajoutez, modifiez vos stocks et gérez vos récoltes publiées sur la marketplace.</p>
                    </div>
                    <button onclick="document.getElementById('modal-add-product').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-700 text-white text-xs sm:text-sm font-bold hover:bg-emerald-800 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Ajouter un nouveau produit</span>
                    </button>
                </div>

                <!-- Products Grid / Table -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @forelse ($products as $prod)
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col justify-between group">
                            <div>
                                <div class="relative h-44 w-full bg-slate-100 overflow-hidden">
                                    <img src="{{ $prod['image'] }}" alt="{{ $prod['nom'] }}" class="h-full w-full object-cover group-hover:scale-105 transition duration-300" />
                                    <span class="absolute top-3 left-3 px-2.5 py-1 rounded-md text-[11px] font-bold bg-white/90 backdrop-blur-sm text-slate-800 shadow-xs">
                                        {{ $prod['categorie'] }}
                                    </span>
                                    <span class="absolute top-3 right-3 px-2 py-0.5 rounded-md text-[10px] font-bold {{ $prod['statut'] === 'En stock' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' }}">
                                        {{ $prod['statut'] }}
                                    </span>
                                </div>
                                <div class="p-4">
                                    <h3 class="font-bold text-sm text-slate-900 line-clamp-1">{{ $prod['nom'] }}</h3>
                                    <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $prod['description'] }}</p>
                                    <div class="mt-3 flex items-center justify-between">
                                        <div class="text-base font-black text-emerald-800">{{ $prod['prix'] }} <span class="text-xs font-normal text-slate-500">/ {{ $prod['unite'] }}</span></div>
                                        <div class="text-xs text-slate-600 font-semibold">Stock : <span class="font-bold text-slate-900">{{ $prod['stock'] }} {{ $prod['unite'] }}s</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 pt-0 border-t border-slate-100 mt-2 flex items-center justify-between gap-2">
                                <a href="{{ route('produit', ['slug' => $prod['slug']]) }}" class="text-xs font-semibold text-slate-600 hover:text-emerald-700 py-2">
                                    Voir la fiche
                                </a>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="document.getElementById('modal-edit-{{ $prod['slug'] }}').classList.remove('hidden')" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">Modifier</button>
                                    <form method="POST" action="{{ route('producer.products.toggle', ['slug' => $prod['slug']]) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg border border-amber-200 text-xs font-semibold text-amber-700 hover:bg-amber-50 transition">
                                            {{ $prod['statut'] === 'En stock' ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('producer.products.delete', ['slug' => $prod['slug']]) }}" onsubmit="return confirm('Supprimer cette offre ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-lg border border-rose-200 text-xs font-semibold text-rose-700 hover:bg-rose-50 transition">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div id="modal-edit-{{ $prod['slug'] }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
                            <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl sm:p-8">
                                <div class="mb-5 flex items-center justify-between border-b border-slate-100 pb-4">
                                    <h3 class="text-lg font-bold text-slate-900">Modifier l'offre</h3>
                                    <button type="button" onclick="document.getElementById('modal-edit-{{ $prod['slug'] }}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600" aria-label="Fermer">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/></svg>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('producer.products.update', ['slug' => $prod['slug']]) }}" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Nom de l'offre *</label>
                                        <input name="nom" value="{{ $prod['nom'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Catégorie *</label>
                                            <input name="categorie" value="{{ $prod['categorie'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Région *</label>
                                            <input name="region" value="{{ $prod['region'] }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm" />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Prix (FCFA) *</label>
                                            <input type="number" name="prix_num" value="{{ $prod['prix_num'] }}" min="100" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Unité *</label>
                                            <input name="unite" value="{{ $prod['unite'] }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 mb-1">Stock *</label>
                                            <input type="number" name="stock" value="{{ $prod['stock'] }}" min="0" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Description *</label>
                                        <textarea name="description" rows="3" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">{{ $prod['description'] }}</textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Image du produit</label>
                                        <div class="flex items-center gap-3 mb-2">
                                            <img src="{{ $prod['image'] }}" alt="{{ $prod['nom'] }}" class="h-12 w-12 rounded-xl object-cover border border-slate-200" />
                                            <span class="text-xs text-slate-500">Image actuelle. Choisissez un nouveau fichier ci-dessous pour la remplacer.</span>
                                        </div>
                                        <div class="space-y-2">
                                            <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/webp" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" />
                                            <input type="url" name="image_url" placeholder="Ou nouvelle URL d'image (optionnel)" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs text-slate-600 focus:border-emerald-600 focus:outline-none" />
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                                        <button type="button" onclick="document.getElementById('modal-edit-{{ $prod['slug'] }}').classList.add('hidden')" class="rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Annuler</button>
                                        <button type="submit" class="rounded-xl bg-emerald-700 px-6 py-2.5 text-xs font-bold text-white hover:bg-emerald-800">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Aucune donnée disponible pour le moment.</div>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- TAB 3 : GESTION DES COMMANDES                                             -->
        <!-- ========================================================================= -->
        @if ($activeTab === 'orders')
            <div class="space-y-6">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900">Gestion des Commandes Reçues</h1>
                    <p class="text-xs sm:text-sm text-slate-500">Consultez les commandes des clients, préparez les colis et mettez à jour leur statut d'expédition.</p>
                </div>

                <div class="space-y-4">
                    @forelse ($orders as $ord)
                        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-sm text-emerald-800">{{ $ord['id'] }}</span>
                                        <span class="text-xs text-slate-400">• Reçue {{ $ord['date'] }}</span>
                                    </div>
                                    <h3 class="font-bold text-base text-slate-900 mt-1">{{ $ord['produit'] }} ({{ $ord['quantite'] }})</h3>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-black text-slate-900">{{ $ord['montant'] }}</div>
                                    <div class="text-xs text-emerald-700 font-semibold">{{ $ord['paiement'] }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 py-4 text-xs text-slate-600">
                                <div>
                                    <span class="text-slate-400 block mb-0.5">Destinataire :</span>
                                    <strong class="text-slate-900">{{ $ord['client_nom'] }}</strong> ({{ $ord['client_phone'] }})
                                </div>
                                <div>
                                    <span class="text-slate-400 block mb-0.5">Adresse de livraison :</span>
                                    <span class="text-slate-800">{{ $ord['client_adresse'] }}</span>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-slate-500">Statut actuel :</span>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $ord['statut'] === 'Livrée' ? 'bg-emerald-100 text-emerald-800' : ($ord['statut'] === 'Expédiée' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $ord['statut'] }}
                                    </span>
                                </div>

                                <!-- Change Status Form -->
                                <form method="POST" action="{{ route('producer.orders.status', ['orderId' => $ord['id']]) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="statut" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-800 focus:border-emerald-600 focus:outline-none">
                                        <option value="En attente" {{ $ord['statut'] === 'En attente' ? 'selected' : '' }}>En attente</option>
                                        <option value="En préparation" {{ $ord['statut'] === 'En préparation' ? 'selected' : '' }}>En préparation</option>
                                        <option value="Expédiée" {{ $ord['statut'] === 'Expédiée' ? 'selected' : '' }}>Expédiée</option>
                                        <option value="Livrée" {{ $ord['statut'] === 'Livrée' ? 'selected' : '' }}>Livrée</option>
                                    </select>
                                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-700 text-white text-xs font-bold hover:bg-emerald-800 transition">
                                        Mettre à jour
                                    </button>
                                </form>
                                @if ($ord['statut'] === 'En attente')
                                    <form method="POST" action="{{ route('producer.orders.status', ['orderId' => $ord['id']]) }}">
                                        @csrf
                                        <input type="hidden" name="statut" value="En préparation">
                                        <button type="submit" class="px-3 py-1.5 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-bold hover:bg-emerald-100 transition">
                                            Confirmer la vente
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Aucune donnée disponible pour le moment.</div>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- TAB 4 : TRANSACTIONS & PORTEFEUILLE                                       -->
        <!-- ========================================================================= -->
        @if ($activeTab === 'transactions')
            <div class="space-y-6">
                <!-- Wallet Balance Card -->
                <div class="bg-gradient-to-br from-slate-900 to-emerald-950 rounded-3xl p-6 sm:p-8 text-white shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Portefeuille Vendeur</span>
                        <h2 class="text-3xl sm:text-4xl font-black text-amber-300 mt-2">{{ number_format($solde, 0, ',', ' ') }} FCFA</h2>
                        <p class="text-xs text-slate-300 mt-1">Disponible pour retrait instantané vers MTN Mobile Money ou Orange Money.</p>
                    </div>
                    <button onclick="document.getElementById('modal-withdraw').classList.remove('hidden')" class="px-5 py-3 rounded-2xl bg-emerald-600 text-white font-bold text-xs sm:text-sm hover:bg-emerald-500 shadow-lg shadow-emerald-900/30 transition">
                        <svg class="mr-1.5 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v18m4-15H9.5a2.5 2.5 0 0 0 0 5h5a2.5 2.5 0 0 1 0 5H8"/></svg> Demander un retrait
                    </button>
                </div>

                <!-- Transactions History -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-slate-100 font-bold text-base text-slate-900">
                        Historique des mouvements financiers
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] text-left text-xs sm:text-sm">
                            <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3.5">Réf. Transaction</th>
                                    <th class="px-5 py-3.5">Date & Heure</th>
                                    <th class="px-5 py-3.5">Description</th>
                                    <th class="px-5 py-3.5">Mode</th>
                                    <th class="px-5 py-3.5">Montant</th>
                                    <th class="px-5 py-3.5">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($transactions as $trx)
                                    <tr>
                                        <td class="px-5 py-4 font-mono font-bold text-slate-800">{{ $trx['id'] }}</td>
                                        <td class="px-5 py-4 text-slate-500">{{ $trx['date'] }}</td>
                                        <td class="px-5 py-4 font-medium text-slate-900">{{ $trx['type'] }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $trx['methode'] ?? 'Mobile Money' }}</td>
                                        <td class="px-5 py-4 font-bold {{ Str::startsWith($trx['montant'], '+') ? 'text-emerald-700' : 'text-rose-600' }}">
                                            {{ $trx['montant'] }}
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-100">
                                                {{ $trx['statut'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- TAB 5 : NOTIFICATIONS                                                     -->
        <!-- ========================================================================= -->
        @if ($activeTab === 'notifications')
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">Centre de Notifications</h1>
                        <p class="text-xs sm:text-sm text-slate-500">Alertes sur vos commandes, messages d'acheteurs et validation administrative.</p>
                    </div>
                    <form method="POST" action="{{ route('producer.notifications.read') }}">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-emerald-700 hover:underline">
                            Tout marquer comme lu
                        </button>
                    </form>
                </div>

                <a href="{{ route('messagerie') }}" class="flex flex-col gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 transition hover:border-emerald-300 hover:bg-emerald-50 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-700 shadow-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-6l-4 3v-3H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/></svg>
                        </span>
                        <span><strong class="block text-sm text-emerald-950">Messages des clients</strong><span class="mt-1 block text-xs text-emerald-800">Consultez et traitez vos conversations depuis votre centre de notifications.</span></span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 sm:shrink-0">Ouvrir la messagerie <span aria-hidden="true">→</span></span>
                </a>

                <div class="space-y-3">
                    @foreach ($notifications as $notif)
                        <div class="p-4 rounded-2xl border {{ $notif['lu'] ? 'border-slate-200 bg-white' : 'border-emerald-200 bg-emerald-50/60' }} shadow-xs flex items-start gap-4">
                            <div class="h-10 w-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center flex-shrink-0 text-lg">
                                @if ($notif['type'] === 'order')
                                    <svg class="h-5 w-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3zM4 7.5l8 4.5 8-4.5M12 12v9"/></svg>
                                @elseif ($notif['type'] === 'message')
                                    <svg class="h-5 w-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-6l-4 3v-3H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/></svg>
                                @else
                                    <svg class="h-5 w-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3 4 6v5c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-3zM9 12l2 2 4-4"/></svg>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-bold text-slate-900">{{ $notif['titre'] }}</h4>
                                    <span class="text-[11px] text-slate-400">{{ $notif['temps'] }}</span>
                                </div>
                                <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $notif['message'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- TAB 6 : SCORE DE FIABILITÉ                                                -->
        <!-- ========================================================================= -->
        @if ($activeTab === 'reliability')
            <div class="space-y-6">
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Algorithme de Confiance AgroNextZone</span>
                            <h1 class="text-2xl font-black text-slate-900 mt-1">Votre Score de Fiabilité Producteur</h1>
                            <p class="text-xs text-slate-500 mt-1">Un score élevé vous positionne en tête des résultats sur la marketplace.</p>
                        </div>
                        <div class="text-center sm:text-right">
                            @if ($reliability['score_global'] !== null)
                                <div class="text-4xl font-black text-emerald-700">{{ $reliability['score_global'] }}<span class="text-xl text-slate-400">/100</span></div>
                                @if ($reliability['badge'])
                                    <span class="mt-1 inline-block rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">{{ $reliability['badge'] }}</span>
                                @endif
                            @else
                                <p class="text-sm font-semibold text-slate-400">Aucune donnée disponible pour le moment.</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-6">
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/60">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-slate-700">Ponctualité Livraison</span>
                                <strong class="text-emerald-700 font-bold">{{ $reliability['ponctualite'] }}%</strong>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2">
                                <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ $reliability['ponctualite'] }}%;"></div>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-2">Respect des délais annoncés aux clients.</p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/60">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-slate-700">Conformité Produits</span>
                                <strong class="text-emerald-700 font-bold">{{ $reliability['conformite_produits'] }}%</strong>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2">
                                <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ $reliability['conformite_produits'] }}%;"></div>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-2">Produits conformes aux photos et descriptions.</p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/60">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-slate-700">Réactivité au Chat</span>
                                <strong class="text-emerald-700 font-bold">{{ $reliability['taux_reponse_chat'] }}%</strong>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2">
                                <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ $reliability['taux_reponse_chat'] }}%;"></div>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-2">Délai moyen de réponse : {{ $reliability['delai_moyen_reponse'] }}.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- TAB 7 : MON COMPTE & EXPLOITATION                                         -->
        <!-- ========================================================================= -->
        @if ($activeTab === 'account')
            <div class="space-y-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Votre identité professionnelle</p>
                    <h1 class="mt-1 text-2xl font-black text-slate-900">Mon profil producteur</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-500">Consultez les informations de votre compte et l’activité visible sur AgroNextZone.</p>
                </div>

                <section class="overflow-hidden rounded-3xl bg-emerald-950 p-5 text-white shadow-lg shadow-emerald-950/10 sm:p-7">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            @if ($user->avatar_url)
                                <img id="producer-profile-avatar" src="{{ $user->avatar_url }}" alt="Photo de {{ $user->name }}" class="h-20 w-20 rounded-2xl border-2 border-emerald-300/50 object-cover" />
                            @else
                                <div id="producer-profile-fallback" class="flex h-20 w-20 items-center justify-center rounded-2xl bg-emerald-700 text-2xl font-black text-white">{{ mb_substr($user->name, 0, 1) }}</div>
                                <img id="producer-profile-avatar" src="" alt="Photo de {{ $user->name }}" class="hidden h-20 w-20 rounded-2xl border-2 border-emerald-300/50 object-cover" />
                            @endif
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-300">Producteur</p>
                                <h2 class="mt-1 text-2xl font-black">{{ $user->name }}</h2>
                                <p class="mt-1 text-sm text-emerald-100">{{ $user->region ?: 'Région non renseignée' }}</p>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4 sm:min-w-48">
                            <p class="text-xs text-emerald-200">Statut du compte</p>
                            <p class="mt-1 text-sm font-bold {{ $user->is_verified ? 'text-lime-300' : 'text-amber-300' }}">{{ $user->is_verified ? 'Vérifié' : 'Vérification en attente' }}</p>
                            <p class="mt-1 text-xs text-emerald-100/70">Aucune donnée de CNI affichée</p>
                        </div>
                    </div>
                </section>

                <div class="grid gap-5 lg:grid-cols-2">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4"><h2 class="text-base font-bold text-slate-900">Informations personnelles</h2><span class="text-xs text-slate-400">Compte</span></div>
                        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div><dt class="text-xs text-slate-400">Nom complet</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $user->name ?: 'Non renseigné' }}</dd></div>
                            <div><dt class="text-xs text-slate-400">Téléphone</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $user->phone ?: 'Non renseigné' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-xs text-slate-400">Adresse e-mail</dt><dd class="mt-1 break-all text-sm font-semibold text-slate-800">{{ $user->email }}</dd></div>
                        </dl>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4"><h2 class="text-base font-bold text-slate-900">Localisation</h2><span class="text-xs text-slate-400">Exploitation</span></div>
                        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div><dt class="text-xs text-slate-400">Région</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $user->region ?: 'Non renseignée' }}</dd></div>
                            <div><dt class="text-xs text-slate-400">Adresse enregistrée</dt><dd class="mt-1 text-sm font-semibold text-slate-800">{{ $user->adresse ?: 'Non renseignée' }}</dd></div>
                        </dl>
                    </section>
                </div>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4"><div><h2 class="text-base font-bold text-slate-900">Activité agricole</h2><p class="mt-1 text-xs text-slate-500">Informations disponibles dans votre compte et vos offres.</p></div><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ count($products) }} produit(s)</span></div>
                    <div class="mt-5 grid gap-5 lg:grid-cols-[0.8fr_1.2fr]">
                        <div class="rounded-xl bg-emerald-50 p-4"><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Produits proposés</p>@if (count($products) > 0)<ul class="mt-3 space-y-2">@foreach (array_slice($products, 0, 5) as $product)<li class="text-sm font-semibold text-slate-700">{{ $product['nom'] }}</li>@endforeach</ul>@else<p class="mt-3 text-sm text-slate-500">Aucune donnée disponible pour le moment.</p>@endif</div>
                        <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Présentation de l’activité</p><p class="mt-3 text-sm leading-7 text-slate-600">{{ $user->bio ?: 'Aucune description de votre activité n’a encore été renseignée.' }}</p></div>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs text-slate-500">Produits publiés</p><p class="mt-2 text-2xl font-black text-slate-900">{{ $dashboardStats['products_count'] }}</p></div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs text-slate-500">Commandes reçues</p><p class="mt-2 text-2xl font-black text-slate-900">{{ $dashboardStats['orders_count'] }}</p></div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs text-slate-500">Score moyen</p><p class="mt-2 text-2xl font-black text-emerald-700">{{ $dashboardStats['average_score'] !== null ? number_format($dashboardStats['average_score'], 1, ',', ' ') . ' / 5' : '—' }}</p></div>
                </section>

                <form method="POST" action="{{ route('producer.account.update') }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    @csrf
                    <div class="border-b border-slate-100 pb-4"><h2 class="text-base font-bold text-slate-900">Modifier les informations autorisées</h2><p class="mt-1 text-xs text-slate-500">Seuls les champs actuellement prévus par le système sont modifiables.</p></div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-slate-700">Photo de profil</label><input id="producer-avatar-input" type="file" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-emerald-700" /><p class="mt-1 text-xs text-slate-500">JPG, PNG ou WEBP, 2 Mo maximum. L’aperçu se met à jour immédiatement.</p>@error('avatar')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        @if ($user->avatar_url)
                            <div class="sm:col-span-2 -mt-2"><label class="flex items-center gap-2 text-xs font-medium text-slate-600"><input type="checkbox" name="remove_avatar" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" /> Supprimer ma photo de profil</label></div>
                        @endif
                        <div><label class="mb-1 block text-xs font-bold text-slate-700">Nom complet</label><input type="text" name="name" value="{{ $user->name }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-600 focus:outline-none" /></div>
                        <div><label class="mb-1 block text-xs font-bold text-slate-700">Téléphone</label><input type="text" name="phone" value="{{ $user->phone }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-600 focus:outline-none" /></div>
                        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-bold text-slate-700">Description de l’activité</label><textarea name="bio" rows="4" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-600 focus:outline-none">{{ $user->bio }}</textarea></div>
                    </div>
                    <div class="mt-5 flex justify-end border-t border-slate-100 pt-4"><button type="submit" class="rounded-xl bg-emerald-700 px-6 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800">Enregistrer les modifications</button></div>
                </form>
            </div>
        @endif

    </main>

    <script>
        (function () {
            const input = document.getElementById('producer-avatar-input');
            const avatar = document.getElementById('producer-profile-avatar');
            const fallback = document.getElementById('producer-profile-fallback');

            if (!input || !avatar) return;

            input.addEventListener('change', function () {
                const file = input.files && input.files[0];
                if (!file || !file.type.startsWith('image/')) return;

                avatar.src = URL.createObjectURL(file);
                avatar.classList.remove('hidden');
                if (fallback) fallback.classList.add('hidden');
            });
        })();
    </script>

    <!-- Modal : Ajouter un Produit -->
    <div id="modal-add-product" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 hidden">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-xl w-full p-4 sm:p-6 lg:p-8 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex items-center justify-between pb-3 sm:pb-4 border-b border-slate-100 mb-4 sm:mb-5">
                <h3 class="text-base sm:text-lg font-bold text-slate-900">Mettre une nouvelle récolte en vente</h3>
                <button onclick="document.getElementById('modal-add-product').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 font-bold text-lg p-1" aria-label="Fermer">&times;</button>
            </div>

            <form method="POST" action="{{ route('producer.products.add') }}" enctype="multipart/form-data" class="space-y-3.5 sm:space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nom du produit agricole *</label>
                    <input type="text" name="nom" required placeholder="Ex: Cacao bio de Bafia, Ananas pain de sucre..." class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:outline-none" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Catégorie *</label>
                        <select name="categorie" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:outline-none">
                            <option value="Vivriers & Tubercules">Vivriers & Tubercules</option>
                            <option value="Fruits & Légumes">Fruits & Légumes</option>
                            <option value="Cacao & Café">Cacao & Café</option>
                            <option value="Semences & Grains">Semences & Grains</option>
                            <option value="Élevage & Aviculture">Élevage & Aviculture</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Région d'origine *</label>
                        <input type="text" name="region" required placeholder="Ex: Centre (Bafia)" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Prix (FCFA) *</label>
                        <input type="number" name="prix_num" required min="100" placeholder="2500" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Unité *</label>
                        <select name="unite" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:outline-none">
                            <option value="kg">kg</option>
                            <option value="régime">régime</option>
                            <option value="sac (50kg)">sac (50kg)</option>
                            <option value="pièce">pièce</option>
                            <option value="panier">panier</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Stock dispo *</label>
                        <input type="number" name="stock" required min="1" placeholder="100" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:outline-none" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Photo du produit</label>
                    <div class="space-y-2">
                        <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/webp" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs sm:text-sm text-slate-700 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" />
                        <input type="url" name="image_url" placeholder="Ou URL web d'une image (https://...)" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs text-slate-600 focus:border-emerald-600 focus:outline-none" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Description & Spécificités culturales *</label>
                    <textarea name="description" rows="3" required placeholder="Décrivez votre méthode de récolte, la maturité..." class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:outline-none"></textarea>
                </div>

                <div class="pt-3 sm:pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5 sm:gap-3">
                    <button type="button" onclick="document.getElementById('modal-add-product').classList.add('hidden')" class="px-4 py-2 sm:py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50">Annuler</button>
                    <button type="submit" class="px-5 sm:px-6 py-2 sm:py-2.5 rounded-xl bg-emerald-700 text-white text-xs font-bold hover:bg-emerald-800 transition">Publier l'offre</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal : Demande de Retrait Mobile Money -->
    <div id="modal-withdraw" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 hidden">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-md w-full p-4 sm:p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="text-sm sm:text-base font-bold text-slate-900">Demande de Retrait Mobile Money</h3>
                <button onclick="document.getElementById('modal-withdraw').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 font-bold text-lg p-1" aria-label="Fermer">&times;</button>
            </div>
            <form method="POST" action="{{ route('producer.withdraw') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Opérateur de réception *</label>
                    <select name="operateur" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                        <option value="MTN Mobile Money">MTN Mobile Money (MoMo)</option>
                        <option value="Orange Money">Orange Money</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Numéro de téléphone de réception *</label>
                    <input type="text" name="telephone" value="{{ $user->phone }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Montant à retirer (FCFA) *</label>
                    <input type="number" name="montant" max="{{ $solde }}" min="1000" placeholder="Ex: 50000" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm" />
                    <p class="text-[11px] text-slate-500 mt-1">Solde max : {{ number_format($solde, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-withdraw').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-700">Annuler</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold hover:bg-emerald-800">Confirmer le retrait</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Page Footer -->
    <footer class="w-full py-6 text-center text-xs text-slate-400 border-t border-slate-200/60 bg-white">
        &copy; {{ date('Y') }} AgroNextZone Cameroun — Espace Vendeur Agricole.
    </footer>
</body>
</html>
