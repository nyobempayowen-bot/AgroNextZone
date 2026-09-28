<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $producteur['nom'] }} | AgroNextZone</title>
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
                    <a href="{{ route('a-propos') }}" class="hover:text-emerald-700 transition">À propos</a>
                    @auth
                        <a href="{{ route('mon.profil') }}" class="hover:text-emerald-700 transition">Mon profil</a>
                        <a href="{{ route('panier') }}" class="hover:text-emerald-700 transition">Mon panier</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-emerald-700 transition">Connexion</a>
                        <a href="{{ route('register') }}" class="inline-flex rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">S'inscrire</a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="mb-6 sm:mb-8 rounded-2xl sm:rounded-[32px] border border-emerald-100 bg-gradient-to-r from-emerald-900 via-emerald-800 to-emerald-700 p-5 sm:p-8 text-white shadow-lg">
            <div class="flex flex-col gap-5 sm:gap-6 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-5 text-center sm:text-left">
                    <img src="{{ $producteur->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($producteur->name).'&background=059669&color=fff&size=256' }}" alt="{{ $producteur->name }}" class="h-20 w-20 sm:h-24 sm:w-24 rounded-full border-4 border-white/30 object-cover flex-shrink-0" />
                    <div>
                        <p class="text-xs sm:text-sm uppercase tracking-[0.2em] text-emerald-100">{{ $isCurrentUser ?? false ? 'Profil utilisateur' : 'Producteur certifié' }}</p>
                        <h1 class="mt-1 sm:mt-2 text-2xl sm:text-3xl font-black">{{ $producteur->name }}</h1>
                        <p class="mt-1 text-xs sm:text-sm text-emerald-100">{{ $producteur->region ?? 'Cameroun' }} · {{ $producteur->is_verified ? 'Producteur Certifié' : 'Producteur' }}</p>
                        @if (($score['count'] ?? 0) > 0)
                            <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-bold text-white">
                                <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 00-.363 1.118l1.286 3.958c.3.922-.755 1.688-1.539 1.118l-3.367-2.446a1 1 0 00-1.175 0l-3.367 2.446c-.783.57-1.838-.196-1.538-1.118l1.285-3.958a1 1 0 00-.363-1.118L2.98 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.286-3.958z"/></svg>
                                {{ number_format($score['average'], 1, ',', ' ') }}/5 · {{ $score['count'] }} avis client{{ $score['count'] > 1 ? 's' : '' }}
                            </p>
                        @else
                            <p class="mt-2 inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">Nouveau producteur · Pas encore d'avis client</p>
                        @endif
                    </div>
                </div>

                <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-emerald-700 hover:bg-emerald-50 transition w-full sm:w-auto">Retour à l'accueil</a>
            </div>
        </div>

        <div class="grid gap-6 lg:gap-8 lg:grid-cols-[0.9fr_1.1fr]">
            <aside class="rounded-2xl sm:rounded-[30px] border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900">Coordonnées</h2>
                <div class="mt-4 space-y-3.5 text-xs sm:text-sm text-slate-600">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Email</p>
                        <p class="mt-0.5 font-medium text-slate-800 break-all">{{ $producteur->email }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Téléphone</p>
                        <p class="mt-0.5 font-medium text-slate-800">{{ $producteur->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Localisation</p>
                        <p class="mt-0.5 font-medium text-slate-800">{{ $producteur->adresse ? $producteur->adresse.' — ' : '' }}{{ $producteur->region ?? '—' }}</p>
                    </div>
                </div>
            </aside>

            <section class="rounded-2xl sm:rounded-[30px] border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900">Présentation de l'activité</h2>
                <p class="mt-3 sm:mt-4 text-xs sm:text-sm text-slate-600 leading-relaxed">{{ $producteur->bio ?? 'Ce producteur n\'a pas encore renseigné de présentation.' }}</p>

                {{-- ========== NOTATION DU PRODUCTEUR ========== --}}
                @php $pStats = $stats ?? ['average' => null, 'count' => 0, 'distribution' => []]; @endphp
                <div class="mt-6 border-t border-slate-100 pt-5">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <h3 class="text-sm font-black text-slate-900">Note du producteur</h3>
                        @if ($pStats['count'] > 0)
                            <p class="text-xs text-slate-500">
                                <span class="font-black text-slate-900">{{ $pStats['count'] }}</span> avis vérifié{{ $pStats['count'] > 1 ? 's' : '' }}
                            </p>
                        @endif
                    </div>

                    @if ($pStats['count'] > 0)
                        {{-- Note moyenne réelle, calculée depuis MySQL --}}
                        <div class="mt-3 flex items-center gap-3">
                            <span class="text-4xl font-black leading-none text-slate-950">{{ number_format((float) $pStats['average'], 1, ',', ' ') }}</span>
                            <div>
                                <x-star-rating :note="$pStats['average']" size="h-4 w-4" />
                                <p class="mt-1 text-xs text-slate-500">sur 5</p>
                            </div>
                        </div>

                        {{-- Répartition par note --}}
                        <div class="mt-4 space-y-1.5">
                            @for ($star = 5; $star >= 1; $star--)
                                @php $nb = $pStats['distribution'][$star] ?? 0; $pct = $pStats['count'] > 0 ? round($nb * 100 / $pStats['count']) : 0; @endphp
                                <div class="flex items-center gap-2">
                                    <span class="w-10 shrink-0 text-xs font-semibold text-slate-600">{{ $star }} étoile{{ $star > 1 ? 's' : '' }}</span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-amber-400" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="w-6 shrink-0 text-right text-xs text-slate-400">{{ $nb }}</span>
                                </div>
                            @endfor
                        </div>

                        {{-- Derniers avis --}}
                        @if (!empty($avis))
                            <ul class="mt-4 space-y-3">
                                @foreach ($avis as $a)
                                    <li class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-xs font-bold text-slate-800">{{ $a['auteur'] }}</span>
                                            <x-star-rating :note="$a['note']" size="h-3.5 w-3.5" />
                                        </div>
                                        @if ($a['commentaire'])
                                            <p class="mt-1.5 text-xs leading-relaxed text-slate-600">{{ $a['commentaire'] }}</p>
                                        @endif
                                        <p class="mt-1.5 text-[10px] text-slate-400">Achat vérifié · <time datetime="{{ $a['date_iso'] }}">{{ $a['date'] }}</time></p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @else
                        {{-- Aucune notation : aucune valeur inventée --}}
                        <div class="mt-3 flex items-center gap-2 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3">
                            <x-star-rating :note="null" size="h-4 w-4" />
                            <p class="text-xs font-semibold text-slate-600">Pas encore noté</p>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Ce producteur n'a pas encore reçu d'avis. Ils apparaîtront ici après les premières commandes.</p>
                    @endif

                    {{-- Appel à l'action : proposé uniquement si une notation
                         est réellement possible pour le visiteur courant. --}}
                    @if (!empty($notationOrder))
                        <div class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50/60 p-3">
                            <p class="text-xs font-semibold text-slate-700">Vous avez commandé chez ce producteur</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">Partagez votre expérience de la commande {{ $notationOrder->reference }}.</p>
                            <a href="{{ route('client.dashboard', ['tab' => 'orders']) }}#notation-{{ $notationOrder->id }}"
                               class="mt-2.5 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700 sm:w-auto">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 00-.363 1.118l1.286 3.958c.3.922-.755 1.688-1.539 1.118l-3.367-2.446a1 1 0 00-1.175 0l-3.367 2.446c-.783.57-1.838-.196-1.538-1.118l1.285-3.958a1 1 0 00-.363-1.118L2.98 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.286-3.958z"/></svg>
                                Noter le producteur
                            </a>
                        </div>
                    @elseif (($isCurrentUser ?? false))
                        <p class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                            Vous ne pouvez pas noter votre propre compte.
                        </p>
                    @endif
                </div>

                <div class="mt-6 sm:mt-8 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 sm:p-5">
                    <p class="text-xs uppercase tracking-[0.18em] text-emerald-700 font-bold">{{ $isCurrentUser ?? false ? 'Mon compte' : 'Contact direct' }}</p>
                    <p class="mt-1.5 text-xs sm:text-sm text-slate-700 leading-relaxed">{{ $isCurrentUser ?? false ? 'Voici les informations de votre compte AgroNextZone. Vous pouvez consulter vos données et gérer votre accès.' : 'Ce producteur est visible dans la marketplace et peut être contacté directement pour une commande ou un achat de récolte.' }}</p>
                    @if (!($isCurrentUser ?? false))
                        <a href="{{ route('discussion', ['id' => $producteur->id]) }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-sky-500 to-indigo-500 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-md shadow-sky-500/20 hover:brightness-110 transition w-full sm:w-auto">Discuter avec le producteur</a>
                    @endif
                </div>
            </section>
        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500 mt-auto">
        &copy; {{ date('Y') }} AgroNextZone — Marketplace Agricole du Cameroun.
    </footer>
</body>
</html>
