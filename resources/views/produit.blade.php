<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $produit['nom'] }} | AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-100 text-slate-800 antialiased min-h-screen flex flex-col">
    @include('components.sidebar')

    <header class="border-b border-emerald-100 bg-white/90 backdrop-blur-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="p-2 rounded-lg hover:bg-slate-100 transition text-slate-700 md:hidden" aria-label="Ouvrir le menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <div class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-700 shadow-sm ring-1 ring-emerald-200 flex-shrink-0">
                            <span class="text-sm sm:text-base font-black text-white tracking-tight">AN</span>
                        </div>
                        <div class="flex flex-col leading-none">
                            <span class="text-base sm:text-lg font-black text-emerald-900">AgroNextZone</span>
                            <span class="mt-0.5 sm:mt-1 text-[9px] sm:text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-700/80">Marketplace</span>
                        </div>
                    </a>
                </div>

                <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-700">
                    <a href="{{ route('home') }}" class="rounded-xl bg-emerald-50 px-3 py-2 text-emerald-700">Accueil</a>
                    <a href="{{ route('a-propos') }}" class="hover:text-emerald-700 transition {{ request()->routeIs('a-propos') ? 'text-emerald-700 font-bold' : '' }}">À propos</a>
                    @auth
                        <a href="{{ route('mon.profil') }}" class="hover:text-emerald-700 transition">Mon profil</a>
                        @if (Auth::user()->role === 'client')
                            <a href="{{ route('panier') }}" class="hover:text-emerald-700 transition">Mon panier</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="hover:text-emerald-700 transition">Connexion</a>
                        <a href="{{ route('register') }}" class="inline-flex rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">S'inscrire</a>
                    @endauth
                </nav>

                <div class="flex items-center gap-2 md:hidden">
                    @auth
                        @if (Auth::user()->role === 'client')
                            <a href="{{ route('panier') }}" class="p-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" aria-label="Panier">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.10),_transparent_38%)]">
        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <div class="mb-5 flex items-center gap-2 text-xs text-slate-500">
                <a href="{{ route('home') }}" class="font-semibold text-emerald-700 hover:underline">Marketplace</a>
                <span>/</span>
                <span class="truncate">{{ $produit['nom'] }}</span>
            </div>

            <section class="overflow-hidden rounded-[26px] border border-emerald-100 bg-white shadow-[0_18px_50px_-28px_rgba(6,95,70,0.45)]">
                <div class="grid lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
                    <div class="relative flex flex-col items-center justify-center bg-emerald-50/70 p-5 sm:p-8 lg:min-h-[440px]">
                        <div class="pointer-events-none absolute inset-0 opacity-40 [background-image:linear-gradient(rgba(5,150,105,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(5,150,105,0.08)_1px,transparent_1px)] [background-size:28px_28px]"></div>
                        <div class="relative aspect-square w-full max-w-[390px] overflow-hidden rounded-2xl border border-white bg-white p-3 shadow-xl shadow-emerald-900/10 sm:p-5">
                            <img id="main-product-image" src="{{ $produit['image'] }}" alt="{{ $produit['nom'] }}" class="h-full w-full object-contain" />
                        </div>
                        @if (!empty($produit['badge']))
                            <span class="absolute left-6 top-6 rounded-full bg-amber-400 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-amber-950 shadow-sm sm:left-10 sm:top-8">{{ $produit['badge'] }}</span>
                        @endif
                        @if (!empty($produit['images']) && count($produit['images']) > 1)
                            <div class="relative mt-4 flex items-center justify-center gap-2 overflow-x-auto py-1">
                                @foreach ($produit['images'] as $imgUrl)
                                    <button type="button" onclick="document.getElementById('main-product-image').src = '{{ $imgUrl }}'" class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-xl border-2 border-transparent hover:border-emerald-500 focus:border-emerald-600 transition bg-white shadow-xs">
                                        <img src="{{ $imgUrl }}" alt="" class="h-full w-full object-cover" />
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="p-5 sm:p-8 lg:p-10">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-emerald-800">{{ $produit['categorie'] ?? 'Produit agricole' }}</span>
                            <span class="rounded-full {{ ($produit['statut'] ?? '') === 'En stock' ? 'bg-lime-100 text-lime-800' : 'bg-rose-100 text-rose-800' }} px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em]">{{ $produit['statut'] ?? 'Disponible' }}</span>
                        </div>
                        <h1 class="mt-4 max-w-2xl text-3xl font-black leading-tight text-slate-950 sm:text-4xl">{{ $produit['nom'] }}</h1>

                        <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2">
                            <span class="text-3xl font-black tracking-tight text-emerald-800">{{ $produit['prix'] }}</span>
                            <span class="text-sm text-slate-500">par {{ $produit['unite'] ?? 'unité' }}</span>
                            @if (!empty($produit['prix_barre']))
                                <span class="text-sm text-slate-400 line-through">{{ $produit['prix_barre'] }}</span>
                            @endif
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-600">
                            @if (($produit['avis_count'] ?? 0) > 0)
                                <a href="#evaluations" class="inline-flex items-center gap-1.5 font-bold text-amber-600 hover:underline">
                                    <x-star-rating :note="$produit['note']" size="h-3.5 w-3.5" />
                                    {{ number_format((float) $produit['note'], 1, ',', ' ') }} / 5
                                </a>
                                <a href="#evaluations" class="hover:underline">{{ $produit['avis_count'] }} avis</a>
                            @else
                                <span class="text-slate-400">Pas encore d'avis</span>
                            @endif
                            <span class="inline-flex items-center gap-1.5"><svg class="h-4 w-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s7-4.35 7-10V5l-7-3-7 3v6c0 5.65 7 10 7 10z"/></svg>{{ $produit['stock'] ?? '—' }} en stock</span>
                        </div>

                        <p class="mt-6 max-w-2xl text-sm leading-7 text-slate-600">{{ $produit['description'] }}</p>

                        <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row">
                            @auth
                                @if (Auth::user()->role === 'client')
                                    <form method="POST" action="{{ route('panier.ajouter', ['slug' => $produit['slug']]) }}" class="sm:flex-1">
                                        @csrf
                                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-700/20 transition hover:-translate-y-0.5 hover:bg-emerald-800"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h2l2.4 11.2a2 2 0 002 1.6h7.8a2 2 0 001.9-1.4L21 8H6m4 13a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/></svg>Ajouter au panier</button>
                                    </form>
                                @endif
                            @endauth
                            <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-3.5 text-sm font-semibold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800">Continuer les achats</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex items-end justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-emerald-700">Fiche produit</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-950">À propos de ce produit</h2>
                    </div>
                    <span class="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 sm:inline-flex">Informations détaillées</span>
                </div>
                <p class="mt-5 max-w-4xl text-sm leading-7 text-slate-600">{{ $produit['description'] }}</p>
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-emerald-50 p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Catégorie</p><p class="mt-1 text-sm font-bold text-slate-800">{{ $produit['categorie'] ?? '—' }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Unité de vente</p><p class="mt-1 text-sm font-bold text-slate-800">{{ $produit['unite'] ?? '—' }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Localisation</p><p class="mt-1 text-sm font-bold text-slate-800">{{ $produit['region'] ?? '—' }}</p></div>
                    <div class="rounded-xl bg-lime-50 p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-lime-700">Disponibilité</p><p class="mt-1 text-sm font-bold text-slate-800">{{ $produit['statut'] ?? '—' }}</p></div>
                </div>
                @if (!empty($produit['caracteristiques']))
                    <dl class="mt-6 grid gap-x-8 gap-y-4 border-t border-slate-100 pt-6 sm:grid-cols-2">
                        @foreach ($produit['caracteristiques'] as $label => $value)
                            <div class="flex gap-3 text-sm"><dt class="min-w-28 text-slate-400">{{ $label }}</dt><dd class="font-semibold text-slate-700">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                @endif
            </section>

            <section class="mt-6 overflow-hidden rounded-2xl border border-emerald-100 bg-emerald-950 p-5 text-white shadow-lg shadow-emerald-950/10 sm:p-7">
                <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        @php
                            $producerUser = \App\Models\User::find($produit['producteur_id'] ?? null);
                            $producerAvatar = $producerUser?->avatar_url;
                        @endphp
                        <img src="{{ $producerAvatar }}" alt="{{ $produit['producteur'] }}" class="h-16 w-16 rounded-2xl border-2 border-emerald-300/50 object-cover" />
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-emerald-300">Producteur associé</p>
                            <h2 class="mt-1 text-xl font-black">{{ $produit['producteur'] }}</h2>
                            <p class="mt-1 text-sm text-emerald-100">{{ $produit['region'] }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="{{ route('profil', ['id' => $produit['producteur_id'] ?? 1]) }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 text-xs font-bold text-emerald-900 transition hover:bg-emerald-50">Voir le profil</a>
                        <a href="{{ route('discussion', ['id' => $produit['producteur_id'] ?? 1]) }}" class="inline-flex items-center justify-center rounded-xl border border-emerald-400/50 px-4 py-3 text-xs font-bold text-white transition hover:bg-emerald-800">Discuter</a>
                    </div>
                </div>
                <div class="mt-6 grid gap-3 border-t border-emerald-800 pt-5 text-sm sm:grid-cols-3">
                    <div><p class="text-xs text-emerald-300">Activité</p><p class="mt-1 font-semibold">Production agricole locale</p></div>
                    <div><p class="text-xs text-emerald-300">Produit évalué</p><p class="mt-1 font-semibold">{{ $produit['note'] ?? '—' }} / 5 · {{ $produit['avis_count'] ?? 0 }} avis</p></div>
                    <div><p class="text-xs text-emerald-300">Profil public</p><p class="mt-1 font-semibold">Informations disponibles</p></div>
                </div>
            </section>

            {{-- ========== ÉVALUATIONS ========== --}}
            @php
                $stats = $reviewStats ?? ['average' => null, 'count' => 0, 'distribution' => []];
            @endphp
            <section id="evaluations" class="mt-6 scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-emerald-700">Retours clients</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-950">Évaluations</h2>
                    </div>
                    @if ($stats['count'] > 0)
                        <div class="flex items-center gap-3">
                            <span class="text-3xl font-black leading-none text-slate-950">{{ number_format((float) $stats['average'], 1, ',', ' ') }}</span>
                            <div>
                                <x-star-rating :note="$stats['average']" size="h-4 w-4" />
                                <p class="mt-1 text-xs text-slate-500">{{ $stats['count'] }} avis vérifié{{ $stats['count'] > 1 ? 's' : '' }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($stats['count'] > 0)
                    {{-- Répartition par note --}}
                    <div class="mt-5 space-y-2">
                        @for ($star = 5; $star >= 1; $star--)
                            @php $nb = $stats['distribution'][$star] ?? 0; $pct = $stats['count'] > 0 ? round($nb * 100 / $stats['count']) : 0; @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-8 shrink-0 text-xs font-bold text-slate-600">{{ $star }} ★</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-amber-400 transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="w-8 shrink-0 text-right text-xs text-slate-500">{{ $nb }}</span>
                            </div>
                        @endfor
                    </div>

                    {{-- Liste des avis --}}
                    <ul class="mt-6 grid gap-4 md:grid-cols-2">
                        @foreach ($reviews as $review)
                            <li class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-black text-emerald-700">
                                        {{ mb_strtoupper(mb_substr($review['auteur'], 0, 1)) }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <p class="truncate text-sm font-bold text-slate-800">{{ $review['auteur'] }}</p>
                                            @if ($review['achat_verifie'])
                                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    Achat vérifié
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-1 flex items-center gap-2">
                                            <x-star-rating :note="$review['note']" size="h-3.5 w-3.5" />
                                            <time datetime="{{ $review['date_iso'] }}" class="text-xs text-slate-400">{{ $review['date'] }}</time>
                                        </div>
                                    </div>
                                </div>
                                @if ($review['titre'] !== '')
                                    <p class="mt-3 text-sm font-semibold text-slate-800">{{ $review['titre'] }}</p>
                                @endif
                                @if ($review['commentaire'] !== '')
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $review['commentaire'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    {{-- Aucun avis : présentation propre, sans donnée inventée --}}
                    <div class="mt-5 flex flex-col items-center gap-2 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                        <svg class="h-9 w-9 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.5l2.2 4.46 4.92.72c.43.06.61.59.28.9l-3.56 3.46.84 4.9c.07.43-.38.76-.77.56L11 16.06l-4.4 2.31c-.39.2-.84-.13-.77-.56l.84-4.9L3.15 9.58c-.33-.31-.15-.84.28-.9l4.92-.72 2.2-4.46c.19-.59 1.05-.59 1.24 0z"/>
                        </svg>
                        <p class="text-sm font-semibold text-slate-700">Aucun avis pour ce produit</p>
                        <p class="max-w-md text-sm text-slate-500">Soyez le premier client à partager son expérience après un achat.</p>
                    </div>
                @endif
            </section>

            {{-- ========== FORMULAIRE (clients connectés) ========== --}}
            @auth
                @if (Auth::user()->role === 'client')
                    <div class="mt-6">@include('components.review-form', ['produit' => $produit, 'myReview' => $myReview ?? null])</div>
                @endif
            @endauth

            @if (!empty($similarProducts))
                <section class="mt-6 pb-4">
                    <div class="mb-4 flex items-end justify-between"><div><p class="text-[10px] font-black uppercase tracking-[0.22em] text-emerald-700">À découvrir</p><h2 class="mt-1 text-2xl font-black text-slate-950">Produits similaires</h2></div><a href="{{ route('home') }}" class="text-xs font-bold text-emerald-700 hover:underline">Voir le marché</a></div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($similarProducts as $similarProduct)
                            <a href="{{ route('produit', ['slug' => $similarProduct['slug']]) }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                                <div class="aspect-[4/3] overflow-hidden bg-slate-100"><img src="{{ $similarProduct['image'] }}" alt="{{ $similarProduct['nom'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" /></div>
                                <div class="p-4"><p class="line-clamp-2 text-sm font-bold text-slate-800">{{ $similarProduct['nom'] }}</p><p class="mt-2 text-sm font-black text-emerald-700">{{ $similarProduct['prix'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $similarProduct['region'] }}</p></div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} AgroNextZone — Marketplace Agricole du Cameroun.
    </footer>

    @auth @if (auth()->user()->role === 'client')
        @include('components.ai-bubble')
    @endif @endauth
</body>
</html>
