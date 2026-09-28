<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marché des prix | AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-100 text-slate-800 antialiased min-h-screen">
    <header class="border-b border-emerald-100 bg-white/90 backdrop-blur-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-700 shadow-sm ring-1 ring-emerald-200">
                        <span class="text-base font-black text-white tracking-tight">AN</span>
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="text-lg font-black text-emerald-900">AgroNextZone</span>
                        <span class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-700/80">Marché des prix</span>
                    </div>
                </div>
                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-700">
                    <a href="{{ route('home') }}" class="hover:text-emerald-700">Accueil</a>
                    <a href="{{ route('marketplace') }}" class="hover:text-emerald-700">Marketplace</a>
                    <a href="{{ route('a-propos') }}" class="hover:text-emerald-700">À propos</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
    <h1 class="text-2xl sm:text-3xl font-black text-slate-900">Marché des prix</h1>
    <p class="mt-2 text-sm text-slate-600">Tendances de prix calculées localement à partir des offres réelles disponibles sur la plateforme (moyenne, minimum, maximum par produit).</p>

    @if ($stats->isEmpty())
        <p class="mt-8 rounded-xl bg-white p-6 text-sm text-slate-500 shadow-sm">Aucune offre disponible pour le moment.</p>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($stats as $s)
                <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900">{{ $s['name'] }}</h2>
                        @if ($s['category'])
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">{{ $s['category'] }}</span>
                        @endif
                    </div>

                    @php
                        $span = max($s['max'] - $s['min'], 0.0001);
                        $avgPos = min(max((($s['avg'] - $s['min']) / $span) * 100, 0), 100);
                    @endphp
                    <div class="mt-4">
                        <div class="relative h-2.5 w-full rounded-full bg-slate-100">
                            <div class="absolute -top-1 h-4 w-1.5 rounded-full bg-emerald-600" style="left: {{ $avgPos }}%"></div>
                        </div>
                        <div class="mt-1 flex justify-between text-[10px] font-semibold text-slate-400">
                            <span>min</span><span>moy.</span><span>max</span>
                        </div>
                    </div>

                    <div class="mt-4 grid-cols-3 gap-2 text-center">
                        <div class="rounded-lg bg-slate-50 py-2">
                            <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Min</div>
                            <div class="text-sm font-bold text-emerald-700">{{ number_format($s['min'], 0, ',', ' ') }} F</div>
                        </div>
                        <div class="rounded-lg bg-emerald-50 py-2">
                            <div class="text-[10px] font-bold uppercase tracking-wide text-emerald-600">Moyen</div>
                            <div class="text-sm font-black text-emerald-800">{{ number_format($s['avg'], 0, ',', ' ') }} F</div>
                        </div>
                        <div class="rounded-lg bg-slate-50 py-2">
                            <div class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Max</div>
                            <div class="text-sm font-bold text-rose-600">{{ number_format($s['max'], 0, ',', ' ') }} F</div>
                        </div>
                    </div>

                    <p class="mt-3 text-[11px] text-slate-400">Basé sur {{ $s['offers'] }} offre(s) — prix par {{ $s['unit'] }}</p>
                </div>
            @endforeach
        </div>
    @endif
    </main>
</body>
</html>
