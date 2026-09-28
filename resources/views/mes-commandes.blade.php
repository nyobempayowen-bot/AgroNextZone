<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes | AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-100 text-slate-800 antialiased">
    <header class="border-b border-emerald-100 bg-white/90 backdrop-blur-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-700 shadow-sm ring-1 ring-emerald-200">
                        <span class="text-base font-black text-white tracking-tight">AN</span>
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="text-lg font-black text-emerald-900">AgroNextZone</span>
                        <span class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-700/80">Marketplace</span>
                    </div>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-8 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-emerald-700 font-bold">Commandes</p>
                <h1 class="mt-2 text-4xl font-black text-slate-900">Mes commandes</h1>
            </div>
            <a href="{{ route('home') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Retour</a>
        </div>

        @forelse ($orders as $order)
            <div class="rounded-[30px] border border-slate-200 bg-white p-6 shadow-sm mb-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Commande</p>
                        <h2 class="text-xl font-black text-slate-900">{{ $order['id'] }}</h2>
                    </div>
                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Confirmée</span>
                </div>

                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Client</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ $order['nom'] }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $order['telephone'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Adresse</p>
                        <p class="mt-2 text-sm text-slate-700">{{ $order['adresse'] }}</p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    @foreach ($order['items'] as $item)
                        <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-sm text-slate-700">
                            <span>{{ $item['nom'] }} × {{ $item['quantite'] }}</span>
                            <span>{{ number_format(($item['prix_num'] ?? 0) * ($item['quantite'] ?? 1), 0, ',', ' ') }} FCFA</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex items-center justify-between border-t border-slate-200 pt-4 text-lg font-black text-slate-900">
                    <span>Total</span>
                    <span>{{ number_format($order['total'], 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
        @empty
            <div class="rounded-[30px] border border-dashed border-slate-300 bg-white p-10 text-center text-slate-600">
                Aucune commande pour le moment.
            </div>
        @endforelse
    </main>
</body>
</html>
