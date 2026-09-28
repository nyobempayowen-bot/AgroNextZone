<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Client | AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-stone-100 text-slate-800 antialiased min-h-screen flex flex-col">
    @include('components.sidebar')

    @php $activeTab = request()->query('tab', 'overview'); @endphp

    <header class="border-b border-emerald-100 bg-white/90 backdrop-blur-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="p-2 rounded-lg hover:bg-slate-100 transition text-slate-700" aria-label="Ouvrir le menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                        <div class="h-10 w-10 rounded-xl bg-emerald-700 text-white flex items-center justify-center font-black text-base shadow-sm ring-1 ring-emerald-500/50 group-hover:scale-105 transition flex-shrink-0">
                            AN
                        </div>
                        <div class="flex flex-col leading-none">
                            <span class="text-base font-bold text-slate-900 leading-tight">AgroNextZone</span>
                            <span class="text-[10px] font-semibold text-emerald-600 uppercase tracking-widest">Espace Client</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-2.5 sm:gap-3">
                    <a href="{{ route('home') }}" class="hidden sm:inline-flex rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Retour au marché
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                        @csrf
                        <button type="submit" class="rounded-xl border border-slate-300 bg-white px-3 sm:px-3.5 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>

            <!-- Navigation Tabs (Scrollable sur mobile) -->
            <div class="flex overflow-x-auto scrollbar-none gap-2 pb-2 text-xs font-semibold border-t border-slate-100 pt-2">
                <a href="{{ route('client.dashboard', ['tab' => 'overview']) }}" class="whitespace-nowrap px-3.5 py-2 rounded-xl transition {{ $activeTab === 'overview' ? 'bg-emerald-700 text-white font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                    Aperçu
                </a>
                <a href="{{ route('client.dashboard', ['tab' => 'orders']) }}" class="whitespace-nowrap px-3.5 py-2 rounded-xl transition {{ $activeTab === 'orders' ? 'bg-emerald-700 text-white font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                    Mes commandes ({{ $ordersCount ?? 0 }})
                </a>
                <a href="{{ route('client.dashboard', ['tab' => 'account']) }}" class="whitespace-nowrap px-3.5 py-2 rounded-xl transition {{ $activeTab === 'account' ? 'bg-emerald-700 text-white font-bold' : 'text-slate-600 hover:bg-slate-100' }}">
                    Mon profil
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="mb-6 sm:mb-8">
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900">Bienvenue, {{ $user->name ?? 'Client' }}</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Gérez vos commandes, votre panier et vos coordonnées personnelles.</p>
        </div>

        @if ($activeTab === 'overview')
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                    <span>Articles au panier</span>
                    <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl sm:text-3xl font-black text-slate-900">{{ $cartCount }}</p>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-xs text-emerald-700 font-bold">{{ number_format($cartTotal, 0, ',', ' ') }} FCFA</span>
                    <a href="{{ route('panier') }}" class="text-xs font-semibold text-emerald-700 hover:underline">Voir le panier →</a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                    <span>Commandes passées</span>
                    <span class="p-1.5 rounded-lg bg-blue-50 text-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl sm:text-3xl font-black text-slate-900">{{ $ordersCount }}</p>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Historique complet</span>
                    <a href="{{ route('client.dashboard', ['tab' => 'orders']) }}" class="text-xs font-semibold text-emerald-700 hover:underline">Voir mes commandes →</a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                    <span>Mon profil</span>
                    <span class="p-1.5 rounded-lg bg-amber-50 text-amber-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-base font-bold text-slate-900 truncate">{{ $user->name ?? 'Client' }}</p>
                <p class="text-xs text-slate-500 truncate">{{ $user->email ?? '' }}</p>
                <div class="mt-3 pt-2 border-t border-slate-100">
                    <a href="{{ route('client.dashboard', ['tab' => 'account']) }}" class="text-xs font-semibold text-emerald-700 hover:underline">Modifier mes coordonnées →</a>
                </div>
            </div>
        </div>
        @endif

        @if ($activeTab === 'orders')
        <div class="space-y-4">
            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs sm:text-sm font-semibold text-emerald-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs sm:text-sm font-semibold text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif
            @php $ordersList = $orders ?? session('orders', []); @endphp
            @forelse ($ordersList as $ord)
                <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pb-3 border-b border-slate-100">
                        <div>
                            <span class="font-mono text-xs font-bold text-emerald-800">{{ $ord['id'] ?? 'CMD' }}</span>
                            <span class="text-xs text-slate-400 ml-2">{{ $ord['created_at'] ?? 'Récemment' }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm sm:text-base font-black text-slate-900">{{ number_format($ord['total'] ?? 0, 0, ',', ' ') }} FCFA</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ ($ord['statut'] ?? '') === 'Livrée' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $ord['statut'] ?? 'En préparation' }}
                            </span>
                        </div>
                    </div>

                    <div class="py-3 text-xs text-slate-600 space-y-1">
                        <p><strong class="text-slate-800">Livraison à :</strong> {{ $ord['nom'] ?? $user->name }} — {{ $ord['telephone'] ?? '' }}</p>
                        <p><strong class="text-slate-800">Adresse :</strong> {{ $ord['adresse'] ?? '' }}</p>
                        <p><strong class="text-slate-800">Mode de paiement :</strong> {{ $ord['moyen_paiement'] ?? 'Mobile Money' }}</p>
                    </div>

                    @if (!empty($ord['items']))
                        <div class="mt-2 pt-2 border-t border-slate-100 flex flex-wrap gap-2">
                            @foreach ($ord['items'] as $item)
                                <span class="px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-700">
                                    {{ $item['nom'] ?? 'Produit' }} (x{{ $item['quantite'] ?? 1 }})
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Notation du producteur après une transaction réelle --}}
                    @if (!empty($ord['producteur_id']))
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            @if (!empty($ord['mon_avis']))
                                {{-- Avis déjà publié : note + commentaire visibles par le client --}}
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-xs font-bold text-slate-800">Votre note pour {{ $ord['producteur'] ?? 'le producteur' }}</span>
                                        <span class="flex items-center gap-1 text-xs font-bold text-amber-600">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 00-.363 1.118l1.286 3.958c.3.922-.755 1.688-1.539 1.118l-3.367-2.446a1 1 0 00-1.175 0l-3.367 2.446c-.783.57-1.838-.196-1.538-1.118l1.285-3.958a1 1 0 00-.363-1.118L2.98 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.286-3.958z"/></svg>
                                            {{ $ord['mon_avis']['note'] }}/5
                                        </span>
                                    </div>
                                    @if ($ord['mon_avis']['commentaire'])
                                        <p class="mt-1 text-xs text-slate-600 leading-relaxed">{{ $ord['mon_avis']['commentaire'] }}</p>
                                    @endif
                                    <p class="mt-1 text-[10px] text-slate-400">Publié le {{ $ord['mon_avis']['date'] }} · Visible sur le profil public du producteur</p>
                                    <a href="{{ route('profil', ['id' => $ord['producteur_id']]) }}" class="mt-1 inline-block text-[11px] font-bold text-emerald-700 hover:underline">Voir sur le profil du producteur →</a>

                                    {{-- L'avis existe : on propose sa modification (pas un nouveau dépôt). --}}
                                    <details class="group mt-2">
                                        <summary class="cursor-pointer select-none inline-flex items-center gap-1.5 text-[11px] font-bold text-emerald-700 hover:text-emerald-800 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Modifier votre note
                                        </summary>
                                        <div class="mt-2">
                                            @include('components.producer-rating-form', ['ord' => $ord])
                                        </div>
                                    </details>
                                </div>
                            @elseif (!empty($ord['peut_noter']))
                                <details class="group">
                                    <summary class="cursor-pointer select-none inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-800 transition">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 00-.363 1.118l1.286 3.958c.3.922-.755 1.688-1.539 1.118l-3.367-2.446a1 1 0 00-1.175 0l-3.367 2.446c-.783.57-1.838-.196-1.538-1.118l1.285-3.958a1 1 0 00-.363-1.118L2.98 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.286-3.958z"/></svg>
                                        Noter {{ $ord['producteur'] ?? 'le producteur' }}
                                    </summary>
                                    <div class="mt-2">
                                        @include('components.producer-rating-form', ['ord' => $ord])
                                    </div>
                                </details>
                            @elseif (!empty($ord['raison_non_notation']))
                                <p class="text-[11px] text-slate-400 italic">{{ $ord['raison_non_notation'] }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">
                    <p class="text-sm font-semibold text-slate-700">Aucune commande enregistrée pour le moment</p>
                    <p class="text-xs text-slate-500 mt-1">Vos prochaines commandes validées apparaîtront ici.</p>
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center mt-4 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs sm:text-sm font-semibold text-white hover:bg-emerald-700 transition">Explorer le catalogue</a>
                </div>
            @endforelse
        </div>
        @endif

        @if ($activeTab === 'account')
        <div class="max-w-2xl mx-auto">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs sm:text-sm font-semibold text-emerald-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 lg:p-8 shadow-sm">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 mb-5">Mes coordonnées</h2>
                <form method="POST" action="{{ route('client.account.update') }}" enctype="multipart/form-data" class="space-y-4 sm:space-y-5">
                    @csrf
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Photo de profil</label>
                        @if ($user->avatar_url)
                            <img id="client-profile-avatar" src="{{ $user->avatar_url }}" alt="Photo de {{ $user->name }}" class="mb-3 h-16 w-16 rounded-full object-cover" />
                        @else
                            <div id="client-profile-fallback" class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-xl font-black text-emerald-700">{{ mb_substr($user->name, 0, 1) }}</div>
                            <img id="client-profile-avatar" src="" alt="Photo de {{ $user->name }}" class="mb-3 hidden h-16 w-16 rounded-full object-cover" />
                        @endif
                        <input id="client-avatar-input" type="file" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs sm:text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-emerald-700" />
                        <p class="mt-1 text-[11px] text-slate-500">JPG, PNG ou WEBP, 2 Mo maximum. L’aperçu se met à jour immédiatement.</p>
                        @error('avatar')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Nom complet *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition" />

                <script>
                    (function () {
                        const input = document.getElementById('client-avatar-input');
                        const avatar = document.getElementById('client-profile-avatar');
                        const fallback = document.getElementById('client-profile-fallback');

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
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Numéro de téléphone *</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Adresse e-mail</label>
                        <input type="email" value="{{ $user->email }}" disabled class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-xs sm:text-sm text-slate-500 cursor-not-allowed" />
                        <p class="mt-1 text-[11px] text-slate-400">L'adresse e-mail est votre identifiant de connexion et ne peut pas être modifiée.</p>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Adresse de livraison habituelle</label>
                        <input type="text" name="adresse" value="{{ old('adresse', $user->adresse ?? '') }}" placeholder="Ex: Bastos, Yaoundé" class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Région</label>
                        <select name="region" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:outline-none transition">
                            <option value="">Sélectionner une région</option>
                            <option value="Centre" {{ ($user->region ?? '') === 'Centre' ? 'selected' : '' }}>Centre</option>
                            <option value="Littoral" {{ ($user->region ?? '') === 'Littoral' ? 'selected' : '' }}>Littoral</option>
                            <option value="Ouest" {{ ($user->region ?? '') === 'Ouest' ? 'selected' : '' }}>Ouest</option>
                            <option value="Nord" {{ ($user->region ?? '') === 'Nord' ? 'selected' : '' }}>Nord</option>
                            <option value="Sud" {{ ($user->region ?? '') === 'Sud' ? 'selected' : '' }}>Sud</option>
                            <option value="Est" {{ ($user->region ?? '') === 'Est' ? 'selected' : '' }}>Est</option>
                            <option value="Adamaoua" {{ ($user->region ?? '') === 'Adamaoua' ? 'selected' : '' }}>Adamaoua</option>
                            <option value="Nord-Ouest" {{ ($user->region ?? '') === 'Nord-Ouest' ? 'selected' : '' }}>Nord-Ouest</option>
                            <option value="Sud-Ouest" {{ ($user->region ?? '') === 'Sud-Ouest' ? 'selected' : '' }}>Sud-Ouest</option>
                            <option value="Extrême-Nord" {{ ($user->region ?? '') === 'Extrême-Nord' ? 'selected' : '' }}>Extrême-Nord</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 mb-3">Changer le mot de passe</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Mot de passe actuel</label>
                                <input type="password" name="current_password" class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Nouveau mot de passe</label>
                                <input type="password" name="new_password" class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Confirmer le nouveau mot de passe</label>
                                <input type="password" name="new_password_confirmation" class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-xs sm:text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition" />
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 flex justify-end">
                        <button type="submit" class="w-full sm:w-auto rounded-xl bg-emerald-700 px-6 py-2.5 text-xs sm:text-sm font-bold text-white hover:bg-emerald-800 transition shadow-sm">
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500 mt-auto">
        &copy; {{ date('Y') }} AgroNextZone — Espace Client.
    </footer>
</body>
</html>
