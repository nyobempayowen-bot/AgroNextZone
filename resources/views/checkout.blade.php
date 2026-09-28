<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finaliser la commande | AgroNextZone</title>
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

                <div class="flex items-center gap-3">
                    <a href="{{ route('panier') }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-emerald-700 hover:underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>Retour au panier</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="mb-6 sm:mb-8">
            <p class="text-xs sm:text-sm uppercase tracking-[0.2em] text-emerald-700 font-bold">Commande</p>
            <h1 class="mt-1 sm:mt-2 text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900">Finaliser la commande</h1>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs sm:text-sm text-rose-800">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:gap-8 lg:grid-cols-[1.15fr_0.85fr] items-start">
            <section class="rounded-2xl sm:rounded-[30px] border border-slate-200 bg-white p-4 sm:p-6 lg:p-8 shadow-sm">
                <form method="POST" action="{{ route('checkout.submit') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="nom" class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Nom complet *</label>
                        <input id="nom" name="nom" type="text" required value="{{ old('nom', $user->name ?? '') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 sm:py-3 text-xs sm:text-sm focus:border-emerald-500 focus:bg-white focus:outline-none transition" placeholder="Ex: Paul Atangana">
                    </div>

                    <div>
                        <label for="telephone" class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Numéro de téléphone (pour la livraison) *</label>
                        <input id="telephone" name="telephone" type="tel" required pattern="[0-9+\s-]+" value="{{ old('telephone', $user->phone ?? '') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 sm:py-3 text-xs sm:text-sm focus:border-emerald-500 focus:bg-white focus:outline-none transition" placeholder="Ex: 655 00 11 22">
                    </div>

                    <div>
                        <label for="adresse" class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Adresse complète de livraison *</label>
                        <textarea id="adresse" name="adresse" rows="3" required class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 sm:py-3 text-xs sm:text-sm focus:border-emerald-500 focus:bg-white focus:outline-none transition" placeholder="Quartier, ville, point de repère précis...">{{ old('adresse', $user->adresse ?? '') }}</textarea>
                    </div>

                    <!-- Moyen de Paiement -->
                    <div class="pt-2">
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Mode de paiement *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <label class="cursor-pointer rounded-xl border border-slate-200 p-3 flex flex-col items-center justify-center gap-2 relative hover:border-emerald-500 transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/40">
                                <input type="radio" name="moyen_paiement" value="MTN Mobile Money" checked class="accent-emerald-600 absolute top-3 left-3">
                                <img src="{{ asset('images/logo-mtn.jpg') }}" alt="Logo MTN Mobile Money" class="h-10 w-auto object-contain rounded mt-2 mix-blend-multiply">
                                <div class="text-xs text-center">
                                    <div class="font-bold text-slate-900">MTN MoMo</div>
                                    <span class="text-[11px] text-slate-500">Mobile Money</span>
                                </div>
                            </label>

                            <label class="cursor-pointer rounded-xl border border-slate-200 p-3 flex flex-col items-center justify-center gap-2 relative hover:border-emerald-500 transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/40">
                                <input type="radio" name="moyen_paiement" value="Orange Money" class="accent-emerald-600 absolute top-3 left-3">
                                <img src="{{ asset('images/logo-orange.jpg') }}" alt="Logo Orange Money" class="h-10 w-auto object-contain rounded mt-2 mix-blend-multiply">
                                <div class="text-xs text-center">
                                    <div class="font-bold text-slate-900">Orange Money</div>
                                    <span class="text-[11px] text-slate-500">Paiement Mobile</span>
                                </div>
                            </label>

                            <label class="cursor-pointer rounded-xl border border-slate-200 p-3 flex flex-col items-center justify-center gap-2 relative hover:border-emerald-500 transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/40">
                                <input type="radio" name="moyen_paiement" value="Espèces à la livraison" class="accent-emerald-600 absolute top-3 left-3">
                                <div class="h-10 w-10 flex items-center justify-center rounded-full bg-slate-100 text-slate-400 mt-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div class="text-xs text-center">
                                    <div class="font-bold text-slate-900">Cash à la livraison</div>
                                    <span class="text-[11px] text-slate-500">À la réception</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-xs sm:text-sm font-semibold text-white hover:bg-emerald-700 transition shadow-sm">
                        Confirmer et payer la commande
                    </button>
                </form>
            </section>

            <aside class="rounded-2xl sm:rounded-[30px] border border-emerald-100 bg-white p-4 sm:p-6 shadow-sm lg:sticky lg:top-28">
                <h2 class="text-base sm:text-lg font-bold text-slate-900">Résumé de votre panier</h2>

                <div class="mt-4 space-y-3 max-h-72 overflow-y-auto pr-1">
                    @foreach ($items as $item)
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 p-2.5">
                            <img src="{{ $item['image'] ?? 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=500&q=80' }}" alt="{{ $item['nom'] }}" class="h-12 w-12 rounded-lg object-cover flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-xs text-slate-900 truncate">{{ $item['nom'] }}</p>
                                <p class="text-[11px] text-slate-500">Quantité : {{ $item['quantite'] }}</p>
                            </div>
                            <span class="text-xs font-bold text-emerald-700 flex-shrink-0">{{ number_format(($item['prix_num'] ?? 0) * ($item['quantite'] ?? 1), 0, ',', ' ') }} FCFA</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 space-y-2.5 border-t border-slate-200 pt-4 text-xs sm:text-sm text-slate-600">
                    <div class="flex justify-between"><span>Sous-total</span><span class="font-semibold text-slate-800">{{ number_format($total, 0, ',', ' ') }} FCFA</span></div>
                    <div class="flex justify-between"><span>Livraison</span><span class="font-semibold text-slate-800">1 000 FCFA</span></div>
                    <div class="flex justify-between border-t border-slate-100 pt-2 font-black text-slate-900 text-sm sm:text-base">
                        <span>Total à payer</span>
                        <span class="text-emerald-700">{{ number_format($total + 1000, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500 mt-auto">
        &copy; {{ date('Y') }} AgroNextZone — Marketplace Agricole du Cameroun.
    </footer>
</body>
</html>
