<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AgroNextZone | Marketplace Agricole du Cameroun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased flex flex-col">

    @include('components.sidebar')

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- TOP NAVBAR                                                             -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <header class="sticky top-0 z-30 bg-white border-b border-slate-200/80 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">

                <!-- Hamburger Button + Logo -->
                <div class="flex items-center gap-3 flex-shrink-0">
                    <button onclick="toggleSidebar()" class="p-2 rounded-lg hover:bg-slate-100 transition text-slate-700" aria-label="Ouvrir le menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                        <div class="h-9 w-9 rounded-xl bg-emerald-700 text-white flex items-center justify-center font-black text-xs shadow-sm group-hover:scale-105 transition">
                            AN
                        </div>
                        <div class="hidden sm:flex flex-col leading-none">
                            <span class="text-base font-black text-emerald-900 tracking-tight">AgroNextZone</span>
                            <span class="text-[9px] font-semibold text-emerald-600 uppercase tracking-[0.2em]">Marketplace Agricole</span>
                        </div>
                    </a>
                </div>

                <!-- Central Search Bar -->
                <form action="{{ route('home') }}" method="GET" class="flex-1 max-w-2xl hidden sm:flex items-center">
                    <div class="relative flex w-full">
                        <select name="categorie" class="h-10 rounded-l-xl border border-r-0 border-slate-300 bg-slate-50 px-3 text-xs text-slate-700 font-medium focus:outline-none focus:border-emerald-500 min-w-[140px]">
                            <option value="">Toutes catégories</option>
                            <option value="Cacao & Café" {{ ($selectedCategory ?? '') === 'Cacao & Café' ? 'selected' : '' }}>Cacao & Café</option>
                            <option value="Vivriers & Tubercules" {{ ($selectedCategory ?? '') === 'Vivriers & Tubercules' ? 'selected' : '' }}>Vivriers & Tubercules</option>
                            <option value="Fruits & Légumes" {{ ($selectedCategory ?? '') === 'Fruits & Légumes' ? 'selected' : '' }}>Fruits & Légumes</option>
                            <option value="Semences & Grains" {{ ($selectedCategory ?? '') === 'Semences & Grains' ? 'selected' : '' }}>Semences & Grains</option>
                            <option value="Élevage & Aviculture" {{ ($selectedCategory ?? '') === 'Élevage & Aviculture' ? 'selected' : '' }}>Élevage & Aviculture</option>
                        </select>
                        <input type="text" name="recherche" value="{{ $searchQuery ?? '' }}" placeholder="Rechercher un produit, un producteur, une région..." class="flex-1 h-10 border border-slate-300 px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500" />
                        <button type="submit" class="h-10 px-4 rounded-r-xl bg-emerald-700 text-white font-bold text-sm hover:bg-emerald-800 transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <span class="hidden md:inline">Rechercher</span>
                        </button>
                    </div>
                </form>

                <!-- Right-side Nav Items -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- À propos (tous, y compris visiteurs) -->
                    <a href="{{ route('a-propos') }}" class="hidden md:inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold {{ request()->routeIs('a-propos') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-700 hover:bg-slate-100' }} transition">
                        À propos
                    </a>
                    @auth
                        <!-- Dashboard Link -->
                        @if (Auth::user()->role === 'producer')
                            <a href="{{ route('producer.dashboard') }}" class="hidden md:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                Mon espace
                            </a>
                        @else
                            <a href="{{ route('client.dashboard') }}" class="hidden md:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Mon compte
                            </a>
                        @endif

                        @if (Auth::user()->role === 'client')
                            <!-- Cart -->
                            <a href="{{ route('panier') }}" class="relative p-2 rounded-xl hover:bg-slate-100 transition text-slate-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                                @if (($cartCount ?? 0) > 0)
                                    <span class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-amber-500 text-[10px] font-bold text-white">{{ $cartCount }}</span>
                                @endif
                            </a>
                        @endif

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition hidden sm:inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Déconnexion
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Se connecter
                        </a>
                        <a href="{{ route('register') }}" class="px-3.5 py-2 rounded-xl bg-emerald-700 text-xs font-bold text-white hover:bg-emerald-800 transition">
                            S'inscrire
                        </a>
                    @endguest
                </div>
            </div>
        </div>

        <!-- Mobile Search Bar -->
        <div class="sm:hidden px-4 pb-3">
            <form action="{{ route('home') }}" method="GET" class="flex">
                <input type="text" name="recherche" value="{{ $searchQuery ?? '' }}" placeholder="Chercher un produit..." class="flex-1 h-9 rounded-l-lg border border-slate-300 px-3 text-sm focus:outline-none focus:border-emerald-500" />
                <button type="submit" class="h-9 px-3 rounded-r-lg bg-emerald-700 text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </form>
        </div>

        <!-- Secondary Nav : Categories scroll -->
        <div class="bg-emerald-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-1 overflow-x-auto scrollbar-none py-2 text-xs font-medium">
                    <a href="{{ route('home') }}" class="whitespace-nowrap px-3 py-1.5 rounded-lg transition {{ empty($selectedCategory) ? 'bg-emerald-700 text-white font-bold' : 'text-emerald-100 hover:bg-emerald-800' }}">
                        Tout voir
                    </a>
                    @php
                        $categories = ['Cacao & Café', 'Vivriers & Tubercules', 'Fruits & Légumes', 'Semences & Grains', 'Élevage & Aviculture'];
                    @endphp
                    @foreach ($categories as $cat)
                        <a href="{{ route('home', ['categorie' => $cat]) }}" class="whitespace-nowrap px-3 py-1.5 rounded-lg transition {{ ($selectedCategory ?? '') === $cat ? 'bg-emerald-700 text-white font-bold' : 'text-emerald-100 hover:bg-emerald-800' }}">
                            {{ $cat }}
                        </a>
                    @endforeach

                    <span class="w-px h-5 bg-emerald-700 mx-2"></span>

                    <!-- Location Selector -->
                    <form action="{{ route('home') }}" method="GET" class="flex items-center gap-1">
                        @if (!empty($searchQuery))
                            <input type="hidden" name="recherche" value="{{ $searchQuery }}" />
                        @endif
                        @if (!empty($selectedCategory))
                            <input type="hidden" name="categorie" value="{{ $selectedCategory }}" />
                        @endif
                        <svg class="w-3.5 h-3.5 text-emerald-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <select name="region" onchange="this.form.submit()" class="bg-transparent text-emerald-100 text-xs font-medium focus:outline-none cursor-pointer border-none">
                            <option value="" class="text-slate-800">Toutes les régions</option>
                            @php
                                $regions = ['Centre', 'Sud', 'Sud-Ouest', 'Ouest', 'Adamaoua', 'Extrême-Nord', 'Littoral', 'Nord-Ouest', 'Est', 'Nord'];
                            @endphp
                            @foreach ($regions as $reg)
                                <option value="{{ $reg }}" class="text-slate-800" {{ ($selectedRegion ?? '') === $reg ? 'selected' : '' }}>{{ $reg }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1">

        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <!-- HERO SECTION                                                           -->
        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <section class="relative overflow-hidden bg-emerald-900">
            <div class="absolute inset-0">
                <!-- Image de fond du hero : remplacez le fichier public/images/accueil-hero.jpg pour la changer. -->
                <img src="{{ asset('images/accueil-hero.jpg') }}" alt="" class="w-full h-full object-cover opacity-30" />
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-950/90 via-emerald-900/80 to-emerald-800/70"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-emerald-100 backdrop-blur-sm">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Marketplace Cameroun
                    </span>
                    <h1 class="mt-5 text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight">
                        Des produits frais, locaux et dignes de confiance.
                    </h1>
                    <p class="mt-4 text-sm sm:text-base text-emerald-100/80 leading-relaxed max-w-lg">
                        Achetez directement auprès des producteurs camerounais. Fruits, tubercules, cacao, grains — livrés chez vous en 24h à 48h.
                    </p>

                    <!-- Quick Stats -->
                    <div class="mt-8 flex flex-wrap items-center gap-4 sm:gap-6 text-white/90">
                        <div>
                            <span class="text-2xl font-black text-amber-300">{{ count($products) }}+</span>
                            <p class="text-xs text-emerald-200 mt-0.5">Produits disponibles</p>
                        </div>
                        <div class="hidden sm:block w-px h-10 bg-emerald-700"></div>
                        <div>
                            <span class="text-2xl font-black text-amber-300">10</span>
                            <p class="text-xs text-emerald-200 mt-0.5">Régions couvertes</p>
                        </div>
                        <div class="hidden sm:block w-px h-10 bg-emerald-700"></div>
                        <div>
                            <span class="text-2xl font-black text-amber-300">24h</span>
                            <p class="text-xs text-emerald-200 mt-0.5">Livraison rapide</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <!-- PRODUCTS CATALOG                                                       -->
        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">

            <!-- Section Header & Sort -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
                <div>
                    @if (!empty($searchQuery))
                        <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-1">Résultats de recherche</p>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900">
                            « {{ $searchQuery }} »
                            <span class="text-sm font-normal text-slate-500 ml-1">— {{ count($products) }} résultat(s)</span>
                        </h2>
                    @elseif (!empty($selectedCategory))
                        <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-1">Catégorie</p>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900">
                            {{ $selectedCategory }}
                            <span class="text-sm font-normal text-slate-500 ml-1">— {{ count($products) }} offre(s)</span>
                        </h2>
                    @else
                        <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-1">Marketplace</p>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Offres fraîches du jour, directement des champs</h2>
                    @endif
                </div>

                <!-- Sort -->
                <form action="{{ route('home') }}" method="GET" class="flex items-center gap-2 flex-shrink-0">
                    @if (!empty($searchQuery))
                        <input type="hidden" name="recherche" value="{{ $searchQuery }}" />
                    @endif
                    @if (!empty($selectedCategory))
                        <input type="hidden" name="categorie" value="{{ $selectedCategory }}" />
                    @endif
                    @if (!empty($selectedRegion))
                        <input type="hidden" name="region" value="{{ $selectedRegion }}" />
                    @endif
                    <label class="text-xs text-slate-500 font-medium">Trier par :</label>
                    <select name="tri" onchange="this.form.submit()" class="h-9 rounded-xl border border-slate-300 bg-white px-3 text-xs text-slate-700 font-medium focus:outline-none focus:border-emerald-500 cursor-pointer">
                        <option value="populaire" {{ ($selectedSort ?? 'populaire') === 'populaire' ? 'selected' : '' }}>Plus populaires</option>
                        <option value="note" {{ ($selectedSort ?? '') === 'note' ? 'selected' : '' }}>Mieux notés</option>
                        <option value="prix_croissant" {{ ($selectedSort ?? '') === 'prix_croissant' ? 'selected' : '' }}>Prix croissant</option>
                        <option value="prix_decroissant" {{ ($selectedSort ?? '') === 'prix_decroissant' ? 'selected' : '' }}>Prix décroissant</option>
                    </select>
                </form>
            </div>

            <!-- Active Filters -->
            @if (!empty($searchQuery) || !empty($selectedCategory) || !empty($selectedRegion))
                <div class="mb-5 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-slate-500 font-medium">Filtres actifs :</span>
                    @if (!empty($searchQuery))
                        <a href="{{ route('home', array_filter(['categorie' => $selectedCategory ?? null, 'region' => $selectedRegion ?? null])) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 transition">
                            {{ $searchQuery }}
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </a>
                    @endif
                    @if (!empty($selectedCategory))
                        <a href="{{ route('home', array_filter(['recherche' => $searchQuery ?? null, 'region' => $selectedRegion ?? null])) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 transition">
                            {{ $selectedCategory }}
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </a>
                    @endif
                    @if (!empty($selectedRegion))
                        <a href="{{ route('home', array_filter(['recherche' => $searchQuery ?? null, 'categorie' => $selectedCategory ?? null])) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 transition">
                            {{ $selectedRegion }}
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </a>
                    @endif
                    <a href="{{ route('home') }}" class="text-xs font-semibold text-rose-600 hover:underline ml-1">Effacer tout</a>
                </div>
            @endif

            <!-- Product Grid -->
            @if (count($products) > 0)
                <div id="produits" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    @foreach ($products as $produit)
                        @include('components.product-card', ['produit' => $produit])
                    @endforeach
                </div>
            @else
                <div class="py-16 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="m20 20-4-4"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Aucun produit trouvé</h3>
                    <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">Essayez de modifier vos critères de recherche ou de supprimer les filtres actifs.</p>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 mt-5 px-4 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold hover:bg-emerald-800 transition">
                        Voir toutes les offres
                    </a>
                </div>
            @endif
        </section>

        @if (count($mapPoints ?? []) > 0)
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="mb-4 flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-1">Géolocalisation</p>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900">Producteurs proches de votre recherche</h2>
                    </div>
                    <button type="button" id="near-me-btn"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold hover:bg-emerald-800 transition self-start sm:self-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s-7-5.5-7-11a7 7 0 1114 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        Producteurs près de moi
                    </button>
                </div>
                <div id="map"
                     class="h-72 sm:h-96 w-full rounded-2xl border border-slate-200 shadow-sm z-0"
                     data-points="{{ json_encode($mapPoints) }}"
                     data-lat="{{ $clientLat ?? '' }}"
                     data-lng="{{ $clientLng ?? '' }}"></div>
                <p id="map-attribution" class="mt-2 text-[11px] text-slate-400">Carte © Google Maps — un marqueur par producteur géolocalisé.</p>
            </section>

            @if (config('services.google_maps.key'))
            <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initAgroMap&loading=async" async defer></script>
            @endif
            <script>
                window.AGRO_MAP_POINTS = @json($mapPoints);
                window.AGRO_CLIENT_POS = { lat: {{ $clientLat ?? 'null' }}, lng: {{ $clientLng ?? 'null' }} };

                // ── Google Maps : tentative en priorité (clé .env requise). ──
                function initAgroMap() {
                    window.__agroGoogleLoaded = true;
                    clearTimeout(window.__agroMapFallback);
                    var el = document.getElementById('map');
                    if (!el || typeof google === 'undefined' || !google.maps) { initAgroLeaflet(); return; }
                    try {
                        var points = window.AGRO_MAP_POINTS || [];
                        var center = window.AGRO_CLIENT_POS.lat && window.AGRO_CLIENT_POS.lng
                            ? window.AGRO_CLIENT_POS
                            : (points[0] ? { lat: points[0].lat, lng: points[0].lng } : { lat: 5.7, lng: 12.35 });

                        var map = new google.maps.Map(el, {
                            center: center,
                            zoom: window.AGRO_CLIENT_POS.lat ? 10 : 6,
                            mapTypeControl: false,
                            streetViewControl: false,
                        });

                        var bounds = new google.maps.LatLngBounds();
                        points.forEach(function (p) {
                            var marker = new google.maps.Marker({
                                position: { lat: parseFloat(p.lat), lng: parseFloat(p.lng) },
                                map: map,
                                title: p.producer,
                            });
                            var info = new google.maps.InfoWindow({
                                content: '<div style="min-width:180px">'
                                    + '<strong>' + p.producer + '</strong><br>'
                                    + [p.city, p.region].filter(Boolean).join(', ') + '<br>'
                                    + (p.score ? 'Note : ' + p.score + ' / 5<br>' : '')
                                    + '<a href="/producteur/' + p.producer_id + '">Voir ses offres</a>'
                                    + '</div>',
                            });
                            marker.addListener('click', function () { info.open(map, marker); });
                            bounds.extend(marker.getPosition());
                        });

                        if (!window.AGRO_CLIENT_POS.lat && points.length) {
                            map.fitBounds(bounds);
                        }
                    } catch (e) {
                        initAgroLeaflet();
                    }
                }

                // ── Leaflet / OpenStreetMap : secours sans clé. ──
                function initAgroLeaflet() {
                    if (window.__agroLeafletReady) return;
                    window.__agroLeafletReady = true;
                    var head = document.head;
                    var css = document.createElement('link');
                    css.rel = 'stylesheet';
                    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    head.appendChild(css);
                    var js = document.createElement('script');
                    js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    js.onload = function () { initAgroLeafletMap(); };
                    head.appendChild(js);
                }

                function initAgroLeafletMap() {
                    var el = document.getElementById('map');
                    if (!el || typeof L === 'undefined') return;
                    el.innerHTML = '';
                    document.getElementById('map-attribution').textContent = 'Carte © OpenStreetMap — un marqueur par producteur géolocalisé.';
                    var points = window.AGRO_MAP_POINTS || [];
                    var map = L.map(el, { scrollWheelZoom: false });

                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    var bounds = [];
                    points.forEach(function (p) {
                        var pos = [parseFloat(p.lat), parseFloat(p.lng)];
                        var marker = L.marker(pos, { title: p.producer }).addTo(map);
                        marker.bindPopup(
                            '<div style="min-width:180px">'
                            + '<strong>' + p.producer + '</strong><br>'
                            + [p.city, p.region].filter(Boolean).join(', ') + '<br>'
                            + (p.score ? 'Note : ' + p.score + ' / 5<br>' : '')
                            + '<a href="/producteur/' + p.producer_id + '">Voir ses offres</a>'
                            + '</div>'
                        );
                        bounds.push(pos);
                    });

                    if (window.AGRO_CLIENT_POS.lat && window.AGRO_CLIENT_POS.lng) {
                        map.setView([window.AGRO_CLIENT_POS.lat, window.AGRO_CLIENT_POS.lng], 10);
                    } else if (bounds.length === 1) {
                        map.setView(bounds[0], 10);
                    } else if (bounds.length > 1) {
                        map.fitBounds(bounds);
                    } else {
                        map.setView([5.7, 12.35], 6);
                    }
                }

                // Si Google ne se manifeste pas (pas de clé, script bloqué, erreur JS)
                // dans les 4 secondes, on affiche la carte Leaflet à la place.
                window.__agroMapFallback = setTimeout(function () {
                    if (!window.__agroGoogleLoaded) initAgroLeaflet();
                }, 4000);
            </script>

            <script>
                // "Near me": ask the browser for its position, then reload with lat/lng.
                document.getElementById('near-me-btn')?.addEventListener('click', function () {
                    if (!navigator.geolocation) { alert('Géolocalisation non disponible sur ce navigateur.'); return; }
                    navigator.geolocation.getCurrentPosition(function (pos) {
                        var url = new URL(window.location.href);
                        url.searchParams.set('lat', pos.coords.latitude.toFixed(6));
                        url.searchParams.set('lng', pos.coords.longitude.toFixed(6));
                        window.location.href = url.toString();
                    }, function () {
                        alert('Position refusée ou indisponible — le filtre par région reste disponible.');
                    });
                });
            </script>
        @endif

        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <!-- HOW IT WORKS SECTION                                                   -->
        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <section class="bg-white border-t border-slate-200/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
                <div class="text-center mb-10">
                    <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-2">Comment ça marche</p>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">De la plantation à votre table, en 3 étapes</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="mx-auto h-14 w-14 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-xl mb-4">
                            <svg class="w-6 h-6 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm">Parcourez les offres</h3>
                        <p class="mt-2 text-xs text-slate-500 leading-relaxed">Explorez le catalogue de produits frais par catégorie, région ou producteur. Comparez les prix et les avis.</p>
                    </div>

                    <div class="text-center">
                        <div class="mx-auto h-14 w-14 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-xl mb-4">
                            <svg class="w-6 h-6 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm">Commandez & Payez</h3>
                        <p class="mt-2 text-xs text-slate-500 leading-relaxed">Ajoutez au panier et payez via MTN Mobile Money, Orange Money ou en espèces à la livraison.</p>
                    </div>

                    <div class="text-center">
                        <div class="mx-auto h-14 w-14 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center text-xl mb-4">
                            <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        </div>
                        <h3 class="font-bold text-slate-900 text-sm">Recevez chez vous</h3>
                        <p class="mt-2 text-xs text-slate-500 leading-relaxed">Suivez votre commande en temps réel. Livraison en 24h à 48h partout au Cameroun.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <!-- TRUST BADGES                                                           -->
        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <section class="bg-slate-50 border-t border-slate-200/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 text-center">
                    <div class="flex flex-col items-center gap-2">
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900">Producteurs Vérifiés</p>
                            <p class="text-[11px] text-slate-500">CNI validée par notre équipe</p>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-2">
                        <div class="h-10 w-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900">Paiement Sécurisé</p>
                            <p class="text-[11px] text-slate-500">Mobile Money & Espèces</p>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-2">
                        <div class="h-10 w-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900">Chat Direct</p>
                            <p class="text-[11px] text-slate-500">Discutez avec le producteur</p>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-2">
                        <div class="h-10 w-10 rounded-xl bg-purple-50 border border-purple-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900">Avis Clients</p>
                            <p class="text-[11px] text-slate-500">Notes et commentaires réels</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- FOOTER                                                                 -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <footer class="bg-emerald-950 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-8 w-8 rounded-lg bg-emerald-800 flex items-center justify-center font-black text-xs text-white">AN</div>
                        <span class="text-base font-bold">AgroNextZone</span>
                    </div>
                    <p class="text-xs text-emerald-300/70 leading-relaxed">La marketplace agricole de confiance au Cameroun. Du producteur au consommateur, sans intermédiaire.</p>
                </div>

                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-300 mb-3">Catégories</h4>
                    <ul class="space-y-1.5 text-xs text-emerald-100/70">
                        @foreach (['Cacao & Café', 'Vivriers & Tubercules', 'Fruits & Légumes', 'Semences & Grains'] as $footerCat)
                            <li><a href="{{ route('home', ['categorie' => $footerCat]) }}" class="hover:text-white transition">{{ $footerCat }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-300 mb-3">Aide & Contact</h4>
                    <ul class="space-y-1.5 text-xs text-emerald-100/70">
                        <li><a href="{{ route('a-propos') }}" class="hover:text-white transition">À propos de nous</a></li>
                        <li><a href="{{ route('confidentialite') }}" class="hover:text-white transition">Politique de confidentialité</a></li>
                        <li>Politique de livraison</li>
                        <li>Conditions d'utilisation</li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-300 mb-3">Paiements acceptés</h4>
                    <div class="flex flex-col gap-2">
                        <span class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-amber-500/10 text-[11px] font-bold text-amber-200 border border-amber-500/20 w-fit">
                            <img src="{{ asset('images/logo-mtn.jpg') }}" class="h-4 rounded-[3px] bg-white object-contain" alt="MTN MoMo">
                            MTN MoMo
                        </span>
                        <span class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-orange-500/10 text-[11px] font-bold text-orange-200 border border-orange-500/20 w-fit">
                            <img src="{{ asset('images/logo-orange.jpg') }}" class="h-4 rounded-[3px] bg-white object-contain" alt="Orange Money">
                            Orange Money
                        </span>
                        <span class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-slate-500/10 text-[11px] font-bold text-slate-200 border border-slate-500/20 w-fit">
                            <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Espèces à la livraison
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-emerald-800/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-emerald-400/60">
                <p>&copy; {{ date('Y') }} AgroNextZone — Marketplace Agricole du Cameroun.</p>
                <p>Fait avec soin au Cameroun.</p>
            </div>
        </div>
    </footer>

    @auth @if (auth()->user()->role === 'client')
        @include('components.ai-bubble')
    @endif @endauth
    @include('components.product-modal')

    <!-- Add to cart AJAX -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.add-to-cart-form').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const btn = form.querySelector('button[type="submit"]');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '...';
                    btn.disabled = true;

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(form),
                    })
                    .then(r => r.json())
                    .then(data => {
                        btn.innerHTML = 'Ajouté';
                        btn.classList.remove('bg-emerald-700');
                        btn.classList.add('bg-emerald-500');
                        // Update cart badge if exists
                        const badge = document.querySelector('[data-cart-count]');
                        if (badge && data.count) {
                            badge.textContent = data.count;
                            badge.classList.remove('hidden');
                        }
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                            btn.classList.remove('bg-emerald-500');
                            btn.classList.add('bg-emerald-700');
                        }, 1500);
                    })
                    .catch(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
                });
            });
        });
    </script>
</body>
</html>
