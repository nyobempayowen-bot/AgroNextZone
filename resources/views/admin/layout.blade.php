<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield("title", "Administration") | AgroNextZone</title>
    @vite(["resources/css/app.css", "resources/js/app.js"])
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col">

    @include("components.sidebar")

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
                <form method="POST" action="{{ route("logout") }}">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-300 bg-white px-3 sm:px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">Déconnexion</button>
                </form>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        @if (session("success"))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session("success") }}</div>
        @endif
        @if (isset($errors) && $errors->any())
            <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ $errors->first() }}</div>
        @endif

        @yield("content")
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500 mt-auto">
        &copy; {{ date("Y") }} AgroNextZone — Administration.
    </footer>
</body>
</html>
