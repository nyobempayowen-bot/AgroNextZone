<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie | AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-100 text-slate-800 antialiased">
    @include('components.sidebar')

    <header class="sticky top-0 z-30 border-b border-emerald-100 bg-white/95 backdrop-blur-sm">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:h-20 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="rounded-lg p-2 text-slate-700 transition hover:bg-slate-100" aria-label="Ouvrir le menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-700 text-xs font-black text-white">AN</span>
                    <span class="hidden text-base font-black text-emerald-900 sm:inline">AgroNextZone</span>
                </a>
            </div>
            <span class="text-sm font-semibold text-slate-500">{{ Auth::user()->role === 'producer' ? 'Espace producteur' : 'Espace client' }}</span>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Échanges directs</p>
            <h1 class="mt-1 text-2xl font-black text-slate-950 sm:text-3xl">Messagerie</h1>
            <p class="mt-2 text-sm text-slate-500">{{ Auth::user()->role === 'producer' ? 'Répondez aux clients intéressés par vos produits.' : 'Consultez vos conversations avec les producteurs.' }}</p>
        </div>

        <section class="grid gap-5 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-4 sm:p-5">
                    <label for="conversation-search" class="sr-only">Rechercher une conversation</label>
                    <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0z"/></svg>
                        <input id="conversation-search" type="search" placeholder="Rechercher une conversation" class="w-full bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400" />
                    </div>
                </div>

                <div id="conversation-list" class="divide-y divide-slate-100">
                    @forelse ($conversations as $conversation)
                        @php $lastMessage = $conversation['dernier_message']; @endphp
                        <a href="{{ route('discussion', ['id' => $conversation['producteur']['id']]) }}" data-conversation-name="{{ strtolower($conversation['producteur']['nom']) }}" class="conversation-item flex items-center gap-3 p-4 transition hover:bg-emerald-50/60 sm:p-5">
                            <img src="{{ $conversation['producteur']['avatar'] }}" alt="{{ $conversation['producteur']['nom'] }}" class="h-11 w-11 rounded-full object-cover" />
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-3"><strong class="truncate text-sm text-slate-900">{{ $conversation['producteur']['nom'] }}</strong><time class="shrink-0 text-[11px] text-slate-400">{{ $lastMessage['time'] ?? '' }}</time></span>
                                <span class="mt-1 block truncate text-xs text-slate-500">{{ $lastMessage['text'] ?? 'Conversation' }}</span>
                            </span>
                        </a>
                    @empty
                        <div id="empty-conversations" class="p-8 text-center sm:p-10">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 6h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-6l-4 3v-3H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/></svg></div>
                            <p class="mt-4 text-sm font-semibold text-slate-700">Aucune conversation pour le moment.</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Ouvrez une discussion depuis la fiche d’un produit ou le profil d’un producteur.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="hidden min-h-[420px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center lg:flex">
                <div class="max-w-sm"><div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 14h5m-8 7 3.5-3H18a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v9a3 3 0 0 0 3 3h.5L5 21z"/></svg></div><h2 class="mt-4 text-lg font-bold text-slate-900">Sélectionnez une conversation</h2><p class="mt-2 text-sm leading-6 text-slate-500">Choisissez un producteur dans la liste pour consulter vos messages.</p></div>
            </div>
        </section>

        <section class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 text-sm text-slate-600 sm:p-5">
            <p class="font-semibold text-emerald-900">{{ Auth::user()->role === 'producer' ? 'Échanges avec vos clients' : 'Nouvelle conversation' }}</p>
            <p class="mt-1 text-xs leading-5">{{ Auth::user()->role === 'producer' ? 'Les clients peuvent vous contacter depuis une fiche produit ou votre profil public.' : 'Pour démarrer un échange, consultez la fiche d’un produit ou le profil d’un producteur, puis choisissez « Discuter ».' }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($producteurs as $producteur)
                    @if (Auth::user()->role === 'client')
                        <a href="{{ route('discussion', ['id' => $producteur['id']]) }}" class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">{{ $producteur['nom'] }}</a>
                    @endif
                @endforeach
            </div>
        </section>
    </main>

    <script>
        const searchInput = document.getElementById('conversation-search');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = searchInput.value.trim().toLowerCase();
                document.querySelectorAll('.conversation-item').forEach(function (item) {
                    item.classList.toggle('hidden', !item.dataset.conversationName.includes(query));
                });
            });
        }
    </script>
</body>
</html>
