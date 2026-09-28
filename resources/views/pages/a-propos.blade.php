<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À Propos — Africa Next Zone (AgroNextZone)</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .vision-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="bg-stone-100 text-slate-800 antialiased flex flex-col">
    @include('components.sidebar')

    <header class="border-b border-emerald-100 bg-white/90 backdrop-blur-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-700 shadow-sm ring-1 ring-emerald-200 flex-shrink-0">
                        <span class="text-sm sm:text-base font-black text-white tracking-tight">AN</span>
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="text-base sm:text-lg font-black text-emerald-900">AgroNextZone</span>
                        <span class="mt-0.5 sm:mt-1 text-[9px] sm:text-[10px] font-semibold uppercase tracking-[0.22em] text-emerald-700/80">Marketplace</span>
                    </div>
                </a>
                <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-700">
                    <a href="{{ route('home') }}" class="hover:text-emerald-700 transition">Accueil</a>
                    <a href="{{ route('confidentialite') }}" class="hover:text-emerald-700 transition">Confidentialité</a>
                    @auth
                        <a href="{{ route('mon.profil') }}" class="hover:text-emerald-700 transition">Mon profil</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-emerald-700 transition">Connexion</a>
                        <a href="{{ route('register') }}" class="inline-flex rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">S'inscrire</a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-1">
        <!-- Hero -->
        <section class="bg-gradient-to-br from-emerald-950 via-emerald-900 to-emerald-800 text-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 py-14 sm:py-20 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border-white/20 text-[11px] font-bold uppercase tracking-wider mb-5">
                    Notre vision
                </div>
                <h1 class="text-3xl sm:text-4xl font-black leading-tight">Connecter chaque producteur<br>au consommateur, sans intermédiaire.</h1>
                <p class="mt-5 text-sm sm:text-base text-emerald-100/80 max-w-2xl mx-auto leading-relaxed">
                    <strong>Africa Next Zone</strong>, via sa plateforme AgroNextZone, est née d'une conviction simple : au Cameroun, celui qui cultive doit gagner sa vie dignement, et celui qui mange doit payer le juste prix pour des produits frais et traçables.
                </p>
            </div>
        </section>

        <!-- Vision -->
        <section class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
            <div class="grid sm:grid-cols-3 gap-5">
                <div class="vision-card bg-white rounded-2xl border-slate-200 shadow-sm p-6 transition">
                    <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 009-9 9 9 0 00-9-9 9 9 0 00-9 9 9 9 0 009 9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 010 18 15 15 0 010-18z"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900 text-sm mb-2">Une économie du circuit court</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Nous supprimons la chaîne d'intermédiaires qui ampute les revenus des agriculteurs : le prix revient directement dans les mains de ceux qui travaillent la terre.</p>
                </div>
                <div class="vision-card bg-white rounded-2xl border-slate-200 shadow-sm p-6 transition">
                    <div class="h-10 w-10 rounded-xl bg-lime-100 text-lime-700 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900 text-sm mb-2">Transparence & confiance</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Produits vérifiés, producteurs identifiés, avis authentiques et prix du marché affichés en temps réel : chacun peut acheter et vendre en connaissance de cause.</p>
                </div>
                <div class="vision-card bg-white rounded-2xl border-slate-200 shadow-sm p-6 transition">
                    <div class="h-10 w-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900 text-sm mb-2">La technologie au service du terroir</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Paiement Mobile Money, recommandations par intelligence artificielle, assistant repas et messagerie directe : l'innovation adaptée aux réalités locales.</p>
                </div>
            </div>

            <div class="mt-10 bg-emerald-950 rounded-2xl text-white p-8 sm:p-10">
                <h2 class="text-lg font-bold mb-3">Notre ambition pour demain</h2>
                <p class="text-sm text-emerald-100/80 leading-relaxed mb-6">
                    Faire d'AgroNextZone, la marketplace d'Africa Next Zone, la référence : digitaliser les filières vivrières et de rente, former et outiller des milliers de producteurs, et réduire durablement le gaspillage post-récolte grâce à la commande en ligne et à la logistique de proximité.
                </p>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div><p class="text-2xl sm:text-3xl font-black text-lime-300">100%</p><p class="text-[11px] text-emerald-200/70 mt-1">Producteurs vérifiés</p></div>
                    <div><p class="text-2xl sm:text-3xl font-black text-lime-300">0</p><p class="text-[11px] text-emerald-200/70 mt-1">Intermédiaire</p></div>
                    <div><p class="text-2xl sm:text-3xl font-black text-lime-300">Mobile</p><p class="text-[11px] text-emerald-200/70 mt-1">Money intégré</p></div>
                </div>
            </div>

            <!-- Contact -->
            <div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
                <h2 class="text-lg font-bold text-slate-900 mb-2">Parler à l'équipe Africa Next Zone</h2>
                <p class="text-xs text-slate-500 mb-5">Une question, un partenariat, une envie de vendre vos produits avec nous ? Nous répondons à tout le monde.</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 text-xs font-semibold">
                    <a href="mailto:owempay@icloud.com" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        owempay@icloud.com
                    </a>
                    <a href="tel:+237698595773" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                        +237 698 595 773
                    </a>
                    <a href="tel:+237677797864" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                        +237 677 797 864
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500 mt-auto">
        &copy; {{ date('Y') }} Africa Next Zone — AgroNextZone, Marketplace Agricole du Cameroun.
    </footer>
</body>
</html>
