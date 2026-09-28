<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon panier | AgroNextZone</title>
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

                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-700">
                    <a href="{{ route('home') }}" class="hover:text-emerald-700 transition">Accueil</a>
                    <a href="{{ route('home') }}" class="hover:text-emerald-700 transition">Marketplace</a>
                    <a href="{{ route('a-propos') }}" class="hover:text-emerald-700 transition">À propos</a>
                    <a href="{{ route('panier') }}" class="text-emerald-700 font-bold">Panier</a>
                </nav>

                <div class="flex items-center gap-2">
                    <a href="{{ route('home') }}" class="text-xs font-semibold text-emerald-700 hover:underline md:hidden">Marché</a>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs sm:text-sm uppercase tracking-[0.2em] text-emerald-700 font-bold">Votre commande</p>
                <h1 class="mt-1 sm:mt-2 text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900">Mon panier</h1>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto">
                @if (!empty($items))
                    <form method="POST" action="{{ route('panier.vider') }}">
                        @csrf
                        <button type="submit" class="rounded-xl border-rose-200 bg-rose-50 px-4 py-2 text-xs sm:text-sm font-semibold text-rose-700 hover:bg-rose-100 transition">Vider le panier</button>
                    </form>
                @endif
                <a href="{{ route('home') }}" class="rounded-xl border-slate-200 bg-white px-4 py-2 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Continuer les achats</a>
            </div>
        </div>

        <div class="grid gap-6 lg:gap-8 lg:grid-cols-[1.2fr_0.8fr] items-start">
            <section class="space-y-4">
                @forelse ($items as $slug => $item)
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 rounded-2xl sm:rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-3.5 flex-1 min-w-0">
                            <img src="{{ $item['image'] ?? 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=500&q=80' }}" alt="{{ $item['nom'] }}" class="h-16 w-16 sm:h-20 sm:w-20 rounded-xl sm:rounded-2xl object-cover flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <h2 class="text-base sm:text-lg font-bold text-slate-900 truncate">{{ $item['nom'] }}</h2>
                                <p class="text-xs sm:text-sm text-slate-500 truncate">{{ $item['region'] ?? 'Cameroun' }} · {{ $item['producteur'] ?? 'Producteur Partenaire' }}</p>
                                <span class="inline-block mt-1 text-sm sm:text-base font-black text-emerald-700 sm:hidden">{{ $item['prix'] }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-4 border-t sm:border-t-0 border-slate-100 pt-3 sm:pt-0">
                            <form method="POST" action="{{ route('panier.quantite', ['slug' => $slug]) }}" class="inline-flex items-center gap-2">
                                @csrf
                                <label class="text-xs sm:text-sm text-slate-600" for="qte-{{ $slug }}">Quantité</label>
                                <input id="qte-{{ $slug }}" name="quantite" type="number" value="{{ $item['quantite'] }}" min="1" max="{{ $item['stock'] ?? 999 }}" step="1" class="w-16 rounded-lg border-slate-200 bg-white px-2 py-1 text-xs sm:text-sm font-bold text-slate-900 focus:border-emerald-500 focus:outline-none" />
                                <button type="submit" class="rounded-lg border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 transition">Mettre à jour</button>
                            </form>
                            <span class="hidden sm:inline-block text-base sm:text-lg font-black text-emerald-700">{{ $item['prix'] }}</span>

                            <form method="POST" action="{{ route('panier.supprimer', ['slug' => $slug]) }}" class="inline-flex">
                                @csrf
                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 p-1.5 rounded-lg hover:bg-rose-50 transition" title="Supprimer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl sm:rounded-[26px] border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">
                        <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        <p class="text-sm font-semibold text-slate-700">Votre panier est vide</p>
                        <p class="text-xs text-slate-500 mt-1">Explorez nos récoltes locales pour commencer votre commande.</p>
                        <a href="{{ route('home') }}" class="inline-flex items-center justify-center mt-4 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs sm:text-sm font-semibold text-white hover:bg-emerald-700 transition">Explorer le marché</a>
                    </div>
                @endforelse
            </section>

            @if (!empty($items) && count($items) > 0)
            <aside class="rounded-2xl sm:rounded-[28px] border border-emerald-100 bg-white p-5 sm:p-6 shadow-sm lg:sticky lg:top-28">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900">Résumé</h2>

                <div class="mt-4 sm:mt-5 space-y-2.5 text-xs sm:text-sm text-slate-600">
                    <div class="flex justify-between">
                        <span>Sous-total</span>
                        <span class="font-semibold text-slate-800">{{ number_format($total, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Livraison</span>
                        <span class="font-semibold text-slate-800">1 000 FCFA</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Frais de service</span>
                        <span class="text-emerald-700 font-semibold">Gratuit</span>
                    </div>
                </div>

                <div class="mt-4 sm:mt-5 border-t border-slate-200 pt-3 sm:pt-4">
                    <div class="flex items-center justify-between text-base sm:text-lg font-black text-slate-900">
                        <span>Total estimé</span>
                        <span class="text-emerald-700">{{ number_format($total + 1000, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>

                <a href="{{ route('checkout') }}" class="mt-5 sm:mt-6 inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-xs sm:text-sm font-semibold text-white hover:bg-emerald-700 transition shadow-sm">
                    Commander maintenant
                </a>
                <a href="{{ route('home') }}" class="mt-2.5 inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Continuer les achats
                </a>
            </aside>
            @endif
        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500 mt-auto">
        &copy; {{ date('Y') }} AgroNextZone — Marketplace Agricole du Cameroun.
    </footer>
</body>
</html>
