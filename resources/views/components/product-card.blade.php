<article class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col justify-between group transition hover:-translate-y-0.5 hover:shadow-md">
    <a href="{{ route('produit', ['slug' => $produit['slug']]) }}" class="block">
        <!-- Product Image -->
        <div class="relative h-48 sm:h-52 w-full bg-slate-100 overflow-hidden">
            <img src="{{ $produit['image'] }}" alt="{{ $produit['nom'] }}" loading="lazy" class="h-full w-full object-cover group-hover:scale-105 transition duration-300" />
            <span class="absolute top-3 left-3 px-2.5 py-1 rounded-md text-[11px] font-bold bg-white/90 backdrop-blur-sm text-slate-800 shadow-xs">
                {{ $produit['categorie'] ?? 'Produit agricole' }}
            </span>
            <span class="absolute top-3 right-3 px-2 py-0.5 rounded-md text-[10px] font-bold {{ ($produit['statut'] ?? 'En stock') === 'En stock' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' }}">
                {{ $produit['statut'] ?? 'En stock' }}
            </span>
            @if (!empty($produit['badge']))
                <span class="absolute bottom-3 left-3 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500 text-white shadow-sm">
                    {{ $produit['badge'] }}
                </span>
            @endif
        </div>

        <!-- Product Info -->
        <div class="p-4 flex flex-col gap-2">
            <div class="flex items-start justify-between gap-2">
                <h3 class="font-bold text-sm text-slate-900 line-clamp-2 leading-snug flex-1">{{ $produit['nom'] }}</h3>
                @if (($produit['avis_count'] ?? 0) > 0)
                <span class="flex items-center gap-0.5 text-sm font-bold text-amber-500 flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    {{ number_format((float) $produit['note'], 1, ',', ' ') }}
                    <span class="text-[10px] font-normal text-slate-400">({{ $produit['avis_count'] }})</span>
                </span>
                @else
                <span class="flex-shrink-0 text-[10px] font-medium text-slate-400">Pas d'avis</span>
                @endif
            </div>

            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">{{ $produit['description'] ?? 'Produit agricole local disponible auprès de notre réseau de producteurs.' }}</p>

            <!-- Price & Stock -->
            <div class="mt-1 flex items-end justify-between gap-2">
                <div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-lg font-black text-emerald-800">{{ $produit['prix'] }}</span>
                        <span class="text-xs font-normal text-slate-500">/ {{ $produit['unite'] ?? 'unité' }}</span>
                    </div>
                    @if (!empty($produit['prix_barre']))
                        <span class="text-xs text-slate-400 line-through">{{ $produit['prix_barre'] }}</span>
                    @endif
                </div>
                <span class="text-[11px] text-slate-500 font-medium">Stock : <strong class="text-slate-800">{{ $produit['stock'] ?? '—' }}</strong></span>
            </div>

            <!-- Producer -->
            <div class="flex items-center gap-2 border-t border-slate-100 pt-3 mt-1">
                <div class="h-7 w-7 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-[10px] font-black flex-shrink-0">
                    {{ mb_substr($produit['producteur'] ?? 'P', 0, 2) }}
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] text-slate-400">Producteur</p>
                    <p class="text-xs font-semibold text-slate-800 truncate">{{ $produit['producteur'] }}</p>
                </div>
                <span class="ml-auto text-[10px] text-slate-400">{{ $produit['region'] ?? '' }}</span>
            </div>
        </div>
    </a>

    <!-- Action Buttons -->
    <div class="p-4 pt-0 border-t border-slate-100 mt-auto flex items-center justify-between gap-2">
        <a href="{{ route('produit', ['slug' => $produit['slug']]) }}" class="text-xs font-semibold text-slate-600 hover:text-emerald-700 py-2">
            Voir la fiche →
        </a>
        @auth
            @if (Auth::user()->role === 'client')
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('panier.ajouter', ['slug' => $produit['slug']]) }}" class="add-to-cart-form">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-700 text-xs font-bold text-white hover:bg-emerald-800 transition disabled:opacity-50 disabled:cursor-not-allowed" {{ ($produit['statut'] ?? 'En stock') !== 'En stock' ? 'disabled' : '' }}>
                        <svg class="mr-1.5 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h2l2.4 11.2a2 2 0 002 1.6h7.8a2 2 0 001.9-1.4L21 8H6m4 13a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
                        Ajouter
                    </button>
                </form>
                <a href="{{ route('produit.acheter', ['slug' => $produit['slug']]) }}" onclick="event.preventDefault(); document.getElementById('buy-{{ $produit['slug'] }}').submit();" class="px-3 py-1.5 rounded-lg border border-amber-300 bg-amber-50 text-xs font-bold text-amber-800 hover:bg-amber-100 transition {{ ($produit['statut'] ?? 'En stock') !== 'En stock' ? 'pointer-events-none opacity-50' : '' }}">
                    Acheter
                </a>
                <form id="buy-{{ $produit['slug'] }}" method="POST" action="{{ route('produit.acheter', ['slug' => $produit['slug']]) }}" class="hidden">@csrf</form>
            </div>
            @endif
        @else
            <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition">
                Se connecter
            </a>
        @endauth
    </div>
</article>
