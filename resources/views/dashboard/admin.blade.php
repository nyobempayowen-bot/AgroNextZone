<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col">

    @include('components.sidebar')

    <header class="border-b border-slate-200 bg-white sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="p-2 rounded-lg hover:bg-slate-100 transition text-slate-700" aria-label="Ouvrir le menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div>
                        <span class="text-base font-black text-slate-900 leading-none block">AgroNextZone</span>
                        <span class="text-[10px] uppercase tracking-wider text-emerald-700 font-bold">Console Administration</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-300 bg-white px-3 sm:px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">Déconnexion</button>
                </form>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="mb-6 sm:mb-8">
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900">Vue d'ensemble de la plateforme</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Supervision globale des utilisateurs, récoltes et transactions.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                    <span>Clients inscrits</span>
                </div>
                <p class="mt-3 text-2xl sm:text-3xl font-black text-slate-900">{{ number_format($stats['clients'], 0, ',', ' ') }}</p>
                <p class="mt-1 text-xs text-emerald-700 font-medium">{{ number_format($stats['suspended_users'], 0, ',', ' ') }} compte(s) suspendu(s)</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                    <span>Producteurs</span>
                </div>
                <p class="mt-3 text-2xl sm:text-3xl font-black text-slate-900">{{ number_format($stats['producers'], 0, ',', ' ') }}</p>
                <p class="mt-1 text-xs text-slate-500">Offres actives : {{ number_format($stats['active_products'], 0, ',', ' ') }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                    <span>Commandes</span>
                </div>
                <p class="mt-3 text-2xl sm:text-3xl font-black text-slate-900">{{ number_format($stats['orders'], 0, ',', ' ') }}</p>
                <p class="mt-1 text-xs text-slate-500">En cours : {{ number_format($stats['orders_pending'], 0, ',', ' ') }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between text-slate-500 text-xs font-semibold">
                    <span>À traiter</span>
                </div>
                <p class="mt-3 text-2xl sm:text-3xl font-black text-slate-900">{{ $stats['pending_verifications'] + $stats['pending_reports'] }}</p>
                <p class="mt-1 text-xs text-rose-600 font-medium">{{ $stats['pending_verifications'] }} validation(s), {{ $stats['pending_reports'] }} signalement(s)</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 mt-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-slate-900">Volume des ventes (transactions réussies)</h2>
                </div>
                <p class="text-3xl font-black text-emerald-800">{{ number_format($stats['volume'], 0, ',', ' ') }} FCFA</p>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($stats['transactions'], 0, ',', ' ') }} transaction(s) enregistrée(s)</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('admin.users') }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200">Utilisateurs</a>
                    <a href="{{ route('admin.verifications') }}" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Comptes à valider</a>
                    <a href="{{ route('admin.reports') }}" class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Signalements</a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <h2 class="font-bold text-slate-900 mb-4">Activité récente</h2>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($stats['recent_users'] as $recent)
                        <li class="py-2 flex items-center justify-between gap-3">
                            <span class="truncate">Nouveau {{ $recent->role === 'producer' ? 'producteur' : 'client' }} : <strong>{{ $recent->name }}</strong></span>
                            <span class="text-xs text-slate-400 flex-shrink-0">{{ $recent->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                    @foreach ($stats['recent_orders'] as $recentOrder)
                        <li class="py-2 flex items-center justify-between gap-3">
                            <span class="truncate">Commande <strong>{{ $recentOrder->reference }}</strong> — {{ $recentOrder->client?->name ?? $recentOrder->shipping_name }}</span>
                            <span class="text-xs text-slate-400 flex-shrink-0">{{ number_format((float) $recentOrder->total, 0, ',', ' ') }} FCFA</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500 mt-auto">
        &copy; {{ date('Y') }} AgroNextZone — Administration.
    </footer>
</body>
</html>
