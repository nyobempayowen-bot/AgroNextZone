@php
    $isLoggedIn = Auth::check();
    $user = $isLoggedIn ? Auth::user() : null;
    $userRole = $user?->role;
    $sidebarAvatarUrl = $user?->avatar_url; // null si aucune photo valide sur le disque
    $sidebarInitials = $user?->initials ?? '';
@endphp

<!-- Sidebar Overlay -->
<div id="sidebar-overlay" class="pointer-events-none hidden" aria-hidden="true"></div>

<!-- Sidebar Panel -->
<aside id="sidebar-panel" class="fixed top-0 left-0 bottom-0 z-50 w-72 -translate-x-full bg-white shadow-xl transition-transform duration-300 flex flex-col" role="navigation" aria-label="Menu principal">

    <!-- Sidebar Header -->
    <div class="flex items-center justify-between p-4 border-b border-slate-200">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-lg bg-emerald-700 text-white flex items-center justify-center font-black text-xs">AN</div>
            <div>
                <div class="font-bold text-slate-900 text-sm">AgroNextZone</div>
                <div class="text-[10px] text-emerald-600 font-semibold uppercase tracking-wider">Menu</div>
            </div>
        </div>
        <button onclick="toggleSidebar()" class="p-2 rounded-lg hover:bg-slate-100 transition text-slate-500" aria-label="Fermer le menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- User Info (if logged in) -->
    @if($isLoggedIn)
        <div class="p-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
                @if($sidebarAvatarUrl)
                    <img id="sidebar-avatar-image" src="{{ $sidebarAvatarUrl }}" alt="Photo de profil de {{ $user->name }}" width="40" height="40" loading="lazy" decoding="async" class="h-10 w-10 rounded-full object-cover shrink-0 border-2 border-emerald-100 shadow-sm" onerror="window.AgroSidebarAvatarFallback && window.AgroSidebarAvatarFallback()" />
                @endif
                <div id="sidebar-avatar-fallback" @class([
                    'h-10 w-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold shrink-0 border-2 border-emerald-100',
                    'hidden' => (bool) $sidebarAvatarUrl,
                ]) aria-hidden="{{ $sidebarAvatarUrl ? 'true' : 'false' }}">{{ $sidebarInitials }}</div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-bold text-slate-900 truncate">{{ $user->name }}</div>
                    <div class="text-[10px] text-emerald-600 font-semibold uppercase tracking-wider">
                        @if($userRole === 'producer') Producteur
                        @elseif($userRole === 'admin') Administrateur
                        @else Client
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto p-4 space-y-1">

        @if(!$isLoggedIn)
            <!-- Visitor Navigation -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Accueil
            </a>
            <a href="{{ route('marketplace') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3zM4 7.5l8 4.5 8-4.5M12 12v9"/></svg>
                Produits
            </a>
            <a href="{{ route('register') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                S'inscrire
            </a>
            <a href="{{ route('login') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                Se connecter
            </a>

@elseif($userRole === 'producer')
            <!-- Producer Navigation -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Accueil
            </a>
            <a href="{{ route('producer.dashboard', ['tab' => 'overview']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->query('tab') === 'overview' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Tableau de bord
            </a>
            <a href="{{ route('producer.dashboard', ['tab' => 'products']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->query('tab') === 'products' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3zM4 7.5l8 4.5 8-4.5M12 12v9"/></svg>
                Mes produits
            </a>
            <a href="{{ route('producer.dashboard', ['tab' => 'orders']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->query('tab') === 'orders' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 3h9l3 3v15H6V3zm3 6h6M9 13h6M9 17h4"/></svg>
                Mes commandes
            </a>
            <a href="{{ route('producer.dashboard', ['tab' => 'transactions']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->query('tab') === 'transactions' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Transactions
            </a>
<a href="{{ route('producer.dashboard', ['tab' => 'notifications']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->query('tab') === 'notifications' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                Notifications
            </a>
            <a href="{{ route('producer.dashboard', ['tab' => 'reliability']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->query('tab') === 'reliability' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3z"/></svg>
                Mon score
            </a>
            <a href="{{ route('producer.dashboard', ['tab' => 'account']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->query('tab') === 'account' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Mon profil
            </a>

        @elseif($userRole === 'admin')
            <!-- Admin Navigation -->
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Dashboard
            </a>
            <a href="{{ route('admin.users') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('admin.users') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Utilisateurs
            </a>
            <a href="{{ route('admin.verifications') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('admin.verifications') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Producteurs a verifier
            </a>
            <a href="{{ route('admin.products') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('admin.products') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3zM4 7.5l8 4.5 8-4.5M12 12v9"/></svg>
                Produits
            </a>
            <a href="{{ route('admin.orders') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('admin.orders') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 3h9l3 3v15H6V3zm3 6h6M9 13h6M9 17h4"/></svg>
                Commandes
            </a>
            <a href="{{ route('admin.reviews') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('admin.reviews') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3z"/></svg>
                Evaluations
            </a>
            <a href="{{ route('admin.prices') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('admin.prices') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Gestion des prix
            </a>
            <a href="{{ route('admin.dishes') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('admin.dishes*') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13h5.5a2.5 2.5 0 010 5H12m0 0H6.5a2.5 2.5 0 010-5H12m0 5v13m-5.5-8h11"/></svg>
                Plats locaux
            </a>
            <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('admin.settings') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8zm8 4-2.1-.8a6.8 6.8 0 0 0-.7-1.7l.9-2.1-1.5-1.5-2.1.9a6.8 6.8 0 0 0-1.7-.7L12 4h-2l-.8 2.1a6.8 6.8 0 0 0-1.7.7l-2.1-.9-1.5 1.5.9 2.1a6.8 6.8 0 0 0-.7 1.7L2 12v2l2.1.8a6.8 6.8 0 0 0 .7 1.7l-.9 2.1 1.5 1.5 2.1-.9a6.8 6.8 0 0 0 1.7.7L10 20h2l.8-2.1a6.8 6.8 0 0 0 1.7-.7l2.1.9 1.5-1.5-.9-2.1a6.8 6.8 0 0 0 .7-1.7L20 14v-2z"/></svg>
                Parametres
            </a>

        @else
            <!-- Client Navigation -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Accueil
            </a>
            <a href="{{ route('marketplace') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3zM4 7.5l8 4.5 8-4.5M12 12v9"/></svg>
                Produits
            </a>
            <a href="{{ route('panier') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('panier') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                Mon panier
            </a>
            <a href="{{ route('assistant-repas') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('assistant-repas') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8m-7 4h.01M15 13h.01M7 19h10a3 3 0 003-3V8a3 3 0 00-3-3h-1.5L14 3h-4L8.5 5H7a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Assistant repas IA
            </a>
            <a href="{{ route('client.dashboard', ['tab' => 'orders']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->query('tab') === 'orders' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 3h9l3 3v15H6V3zm3 6h6M9 13h6M9 17h4"/></svg>
                Mes commandes
            </a>
<a href="{{ route('messagerie') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->routeIs('messagerie') ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-6l-4 3v-3H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/></svg>
                Messagerie
            </a>
            <a href="{{ route('client.dashboard', ['tab' => 'account']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition {{ request()->query('tab') === 'account' ? 'bg-emerald-100 text-emerald-700' : '' }}">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Mon profil
            </a>
        @endif
    </nav>

    <!-- À propos : commun à tous (visiteur, client, producteur, admin) -->
    <div class="px-4 pb-2">
        <a href="{{ route('a-propos') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('a-propos') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }} transition">
            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            À propos de nous
        </a>
    </div>

    <!-- Logout (if logged in) -->
    @if($isLoggedIn)
        <div class="p-4 border-t border-slate-200">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-rose-600 hover:bg-rose-50 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Deconnexion
                </button>
            </form>
        </div>
    @endif
</aside>

<script>
    // Photo de profil indisponible côté navigateur (fichier supprimé, erreur 404)
    // → repli automatique sur les initiales, sans casser la mise en page.
    window.AgroSidebarAvatarFallback = function () {
        const image = document.getElementById('sidebar-avatar-image');
        const fallback = document.getElementById('sidebar-avatar-fallback');

        if (image) {
            image.classList.add('hidden');
            image.removeAttribute('onerror');
        }
        if (fallback) {
            fallback.classList.remove('hidden');
            fallback.setAttribute('aria-hidden', 'false');
        }
    };

    function toggleSidebar() {
        const panel = document.getElementById('sidebar-panel');

        if (panel.classList.contains('-translate-x-full')) {
            panel.classList.remove('-translate-x-full');
            document.body.classList.add('sidebar-open');
        } else {
            panel.classList.add('-translate-x-full');
            document.body.classList.remove('sidebar-open');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const panel = document.getElementById('sidebar-panel');
            if (!panel.classList.contains('-translate-x-full')) {
                toggleSidebar();
            }
        }
    });
</script>