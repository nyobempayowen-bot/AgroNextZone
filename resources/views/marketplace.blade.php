<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Marketplace | AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-100 text-slate-800 antialiased">
    <header class="border-b border-emerald-100 bg-white/90 backdrop-blur-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-700 shadow-sm ring-1 ring-emerald-200">
                        <span class="text-base font-black text-white tracking-tight">AN</span>
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="text-lg font-black text-emerald-900">AgroNextZone</span>
                        <span class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-700/80">Marketplace</span>
                    </div>
                </div>

                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-700">
                    <a href="{{ route('home') }}" class="hover:text-emerald-700">Accueil</a>
                    <a href="{{ route('marketplace') }}" class="text-emerald-700 font-semibold">Marketplace</a>
                    <a href="#" class="hover:text-emerald-700">À propos</a>
                    <a href="{{ route('login') }}" class="hover:text-emerald-700">Connexion</a>
                </nav>

                <div class="flex items-center gap-3">
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex px-4 py-2 rounded-xl border border-emerald-200 text-emerald-700 font-semibold hover:bg-emerald-50">Se connecter</a>
                    <a href="{{ route('register') }}" class="inline-flex px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700">S'inscrire</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <section class="mb-10">
            <p class="text-sm uppercase tracking-[0.2em] text-emerald-700 font-bold">Marketplace</p>
            <h1 class="mt-3 text-4xl font-black text-slate-900">Produits agricoles du Cameroun</h1>
            <p class="mt-3 max-w-2xl text-slate-600">Découvrez des produits frais, sains et directement issus des exploitations locales.</p>
        </section>

        <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($produits as $produit)
                <article class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <a href="{{ route('produit', ['slug' => $produit['slug']]) }}" class="block">
                        <div class="overflow-hidden">
                            <img src="{{ $produit['image'] }}" alt="{{ $produit['nom'] }}" class="h-56 w-full object-cover transition duration-300 group-hover:scale-105" />
                        </div>
                    </a>

                    <div class="p-5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.15em] text-emerald-700">Disponible</span>
                            @if (($produit['avis_count'] ?? 0) > 0)
                            <span class="inline-flex items-center gap-1 text-sm font-bold text-amber-500"><svg class="h-4 w-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L2 9.6l6.2-.9L12 3z"/></svg> {{ number_format((float) $produit['note'], 1, ',', ' ') }}</span>
                            @else
                            <span class="text-xs text-slate-400">Pas d'avis</span>
                            @endif
                        </div>

                        <a href="{{ route('produit', ['slug' => $produit['slug']]) }}" class="mt-4 block text-xl font-bold text-slate-900 hover:text-emerald-700">{{ $produit['nom'] }}</a>

                        <div class="mt-4 flex items-end justify-between">
                            <div>
                                <p class="text-xs text-slate-500">Région</p>
                                <p class="font-semibold text-slate-800">{{ $produit['region'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-500">Prix</p>
                                <p class="text-xl font-black text-emerald-700">{{ $produit['prix'] }}</p>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-slate-200 pt-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs text-slate-500">Producteur</p>
                                    <p class="font-semibold text-slate-800">{{ $produit['producteur'] }}</p>
                                </div>
                                <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Ajouter</button>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
    </main>
    @auth @if (auth()->user()->role === 'client')
        @include('components.ai-bubble')
    @endif @endauth
    @include('components.product-modal')
</body>
</html>
