<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plats locaux | Admin AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-100 text-slate-800 antialiased min-h-screen">
    <header class="border-b border-emerald-100 bg-white/90 backdrop-blur-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-20">
            <h1 class="text-xl font-black text-emerald-900">Plats locaux — Base de connaissance IA</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-slate-600 hover:text-emerald-700">&larr; Dashboard admin</a>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('success'))
            <div class="mb-4 rounded-xl bg-emerald-50 border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid gap-8 lg:grid-cols-[1fr_1.4fr]">
            {{-- Formulaire ajout / édition --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                <h2 class="text-sm font-black uppercase tracking-wide text-slate-500">
                    {{ $editDish ? 'Modifier : '.$editDish->name : 'Ajouter un plat' }}
                </h2>

                {{-- Saisie assistée par IA (Partie 2-B) : propose une structure, pré-remplit le formulaire. --}}
                @if (! $editDish)
                    <div id="ai-extract-panel" class="mt-3 rounded-xl border border-sky-200 bg-sky-50 p-3">
                        <label class="text-xs font-bold text-sky-900">Saisie assistée par IA (optionnel)</label>
                        <p class="mt-1 text-[11px] text-sky-700">Décrivez le plat en langage libre. L'IA pré-remplit le
                            formulaire ci-dessous — <strong>rien n'est enregistré sans votre validation</strong> via le
                            bouton « Ajouter le plat ».</p>
                        <textarea id="ai-extract-texte" rows="3" placeholder="Ex : Le Ndolé se prépare avec des feuilles de ndolé, de la pâte d'arachide, du poisson fumé, de l'huile de palme et des crevettes séchées."
                            class="mt-2 w-full rounded-lg border-slate-300 px-3 py-2 text-xs"></textarea>
                        <button type="button" id="ai-extract-btn"
                            class="mt-2 rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-sky-700 disabled:opacity-50">
                            Extraire avec l'IA
                        </button>
                        <p id="ai-extract-msg" class="mt-2 hidden text-[11px]"></p>
                    </div>
                @endif

                <form method="POST" class="mt-4 space-y-4"
                      action="{{ $editDish ? route('admin.dishes.update', $editDish) : route('admin.dishes.store') }}">
                    @csrf
                    @if ($editDish) @method('PUT') @endif

                    <div>
                        <label class="text-xs font-bold text-slate-600">Nom du plat *</label>
                        <input type="text" name="name" required value="{{ old('name', $editDish->name ?? '') }}"
                               class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-600">Région (optionnel)</label>
                        <input type="text" name="region" value="{{ old('region', $editDish->region ?? '') }}"
                               class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-600">Description (optionnel)</label>
                        <textarea name="description" rows="2" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm">{{ old('description', $editDish->description ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-600">Ingrédients (un par ligne, suffixe « | optionnel » si variante)</label>
                        <textarea name="ingredients" rows="6" placeholder="Manioc&#10;Huile de palme | optionnel"
                                  class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm font-mono">{{ old('ingredients', $editDish ? $editDish->ingredients->map(fn ($i) => $i->ingredient_name.($i->is_optional ? ' | optionnel' : ''))->implode("\n") : '') }}</textarea>
                        <p class="mt-1 text-[11px] text-slate-400">Ces ingrédients constituent la seule source de vérité envoyée à l'IA.</p>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                            {{ $editDish ? 'Enregistrer' : 'Ajouter le plat' }}
                        </button>
                        @if ($editDish)
                            <a href="{{ route('admin.dishes') }}" class="rounded-xl border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Annuler</a>
                        @endif
                    </div>
                </form>
            </section>

            {{-- Liste des plats --}}
            <section>
                <form method="GET" class="mb-4 flex gap-2">
                    <input type="text" name="recherche" value="{{ $search }}" placeholder="Recher un plat, une région, un ingrédient…"
                           class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                    <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-bold text-white">Chercher</button>
                </form>

                <div class="space-y-3">
                    @forelse ($dishes as $dish)
                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-black text-slate-900">{{ $dish->name }}</h3>
                                    @if ($dish->region)
                                        <span class="text-[11px] font-semibold text-emerald-700">{{ $dish->region }}</span>
                                    @endif
                                    <p class="mt-1 text-xs text-slate-500">{{ $dish->ingredients->pluck('ingredient_name')->implode(', ') }}</p>
                                </div>
                                <div class="flex gap-2 shrink-0">
                                    <a href="{{ route('admin.dishes', ['edit' => $dish->id]) }}" class="text-xs font-bold text-sky-600 hover:underline">Modifier</a>
                                    <form method="POST" action="{{ route('admin.dishes.destroy', $dish) }}" onsubmit="return confirm('Supprimer ce plat ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl bg-white p-6 text-sm text-slate-500 shadow-sm">Aucun plat en base. Ajoutez le premier ci-contre.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </main>

    <script>
        // Partie 2-B — Saisie assistée par IA : appelle l'endpoint d'extraction,
        // pré-remplit le formulaire. RIEN n'est enregistré : l'admin relit et valide.
        (function () {
            var btn = document.getElementById('ai-extract-btn');
            if (!btn) return;
            var texte = document.getElementById('ai-extract-texte');
            var msg = document.getElementById('ai-extract-msg');

            function show(text, isError) {
                msg.textContent = text;
                msg.classList.remove('hidden');
                msg.classList.toggle('text-rose-600', isError);
                msg.classList.toggle('text-emerald-700', !isError);
            }

            btn.addEventListener('click', function () {
                if (!texte.value.trim()) { show('Saisissez d\u2019abord une description du plat.', true); return; }
                btn.disabled = true;
                show('Extraction en cours\u2026', false);

                fetch('{{ route('admin.dishes.extract') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ texte: texte.value }),
                })
                    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
                    .then(function (r) {
                        if (!r.ok) { show(r.data.message || 'Extraction impossible.', true); return; }
                        var d = r.data;
                        document.querySelector('input[name="name"]').value = d.name || '';
                        document.querySelector('input[name="region"]').value = d.region || '';
                        document.querySelector('textarea[name="description"]').value = d.description || '';
                        document.querySelector('textarea[name="ingredients"]').value = (d.ingredients || [])
                            .map(function (i) { return i.name + (i.optional ? ' | optionnel' : ''); }).join('\n');
                        show('Proposition pr\u00e9-remplie ci-dessous \u2014 relisez puis validez avec \u00ab Ajouter le plat \u00bb.', false);
                        document.querySelector('input[name="name"]').focus();
                    })
                    .catch(function () { show('Service IA temporairement indisponible \u2014 utilisez la saisie manuelle.', true); })
                    .finally(function () { btn.disabled = false; });
            });
        })();
    </script>
</body>
</html>
