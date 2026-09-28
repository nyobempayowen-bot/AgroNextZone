<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de Confidentialité — Africa Next Zone (AgroNextZone)</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .prose-page h2 { font-weight: 700; font-size: 1.05rem; color: #064e3b; margin-top: 1.75rem; margin-bottom: .5rem; }
        .prose-page p, .prose-page li { color: #475569; font-size: .9rem; line-height: 1.7; }
        .prose-page ul { list-style: disc; padding-left: 1.25rem; margin-top: .5rem; }
        .prose-page li { margin-bottom: .35rem; }
        .prose-page strong { color: #1e293b; font-weight: 600; }
    </style>
</head>
<body class="bg-stone-100 text-slate-800 antialiased min-h-screen flex flex-col">
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
                    <a href="{{ route('a-propos') }}" class="hover:text-emerald-700 transition">À propos</a>
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

    <main class="flex-1 w-full max-w-4xl mx-auto px-4 sm:px-6 py-10">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-10 prose-page">
            <div class="mb-6 pb-5 border-b border-slate-100">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold uppercase tracking-wider mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    Confidentialité & Protection des données
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900">Politique de Confidentialité</h1>
                <p class="text-xs text-slate-400 mt-2">Dernière mise à jour : {{ now()->translatedFormat('d F Y') }} — AgroNextZone SARL, Cameroun.</p>
            </div>

            <p class="text-sm"><strong>Africa Next Zone</strong>, via sa plateforme AgroNextZone, est la marketplace agricole camerounaise qui connecte directement les producteurs aux consommateurs. La confiance de nos utilisateurs est le socle de notre mission : cette politique explique, en toute transparence, quelles données nous collectons, pourquoi, et comment nous les protégeons. Elle est régie par la loi n° 2010/012 du 21 décembre 2010 relative à la cybersécurité et à la cybercriminalité au Cameroun ainsi que par les bonnes pratiques internationales (RGPD).</p>

            <h2>1. Les données que nous collectons</h2>
            <ul>
                <li><strong>Compte :</strong> nom, prénom, adresse e-mail, numéro de téléphone (vérifié par code OTP), rôle choisi (client ou producteur), localisation et ville de livraison.</li>
                <li><strong>Producteurs :</strong> informations du profil public de la ferme/exploitation, description et images des produits, coordonnées bancaires ou Mobile Money nécessaires aux retraits.</li>
                <li><strong>Transactions :</strong> historique de commandes, paniers, références de paiement, communications avec les producteurs via la messagerie interne.</li>
                <li><strong>Données techniques :</strong> données de session, type d'appareil et journaux de sécurité collectés de manière anonymisée pour la protection de la plateforme.</li>
            </ul>

            <h2>2. Ce que nous faisons de vos données</h2>
            <ul>
                <li>Exécuter vos commandes et permettre la livraison de vos produits agricoles.</li>
                <li>Vérifier l'identité des producteurs et sécuriser les transactions Mobile Money.</li>
                <li>Améliorer votre expérience (recommandations IA, assistant repas, affichage des prix du marché).</li>
                <li>Assurer la modération (avis, produits) et prévenir les fraudes.</li>
            </ul>
            <p><strong>Nous ne vendons jamais vos données personnelles à des tiers.</strong></p>

            <h2>3. Le partage, limité au strict nécessaire</h2>
            <p><strong>Africa Next Zone</strong> ne partage vos données qu'avec : le producteur concerné par votre commande (nom, contacts de livraison, adresse uniquement), nos opérateurs de paiement Mobile Money (MTN MoMo, Orange Money) pour finaliser vos transactions, et les autorités compétentes sur réquisition légale en bonne et due forme.</p>

            <h2>4. Vos conversations restent privées</h2>
            <p>La messagerie client ↔ producteur d'AgroNextZone est un espace confidentiel. Elle n'est consultable que par les deux parties concernées et par les administrateurs de la plateforme exclusivement en cas de signalement d'un litige, dans le cadre de la résolution du différend.</p>

            <h2>5. Vos droits</h2>
            <ul>
                <li><strong>Accès et rectification :</strong> consultez et modifiez vos informations à tout moment depuis l'onglet « Mon compte » de votre espace.</li>
                <li><strong>Effacement :</strong> demandez la suppression de votre compte en contactant notre support ( vos commandes historiques seront conservées de manière anonymisée pour raisons comptables.</li>
                <li><strong>Opposition :</strong> vous pouvez refuser les communications commerciales à tout moment.</li>
            </ul>

            <h2>6. Sécurité</h2>
            <p>Nous appliquons des mesures de protection : hachage sécurisé des mots de passe, vérification OTP des téléphones, sessions chiffrées, contrôle d'accès strict par rôle (client, producteur, administrateur) et limitations des tentatives de connexion.</p>

            <h2>7. Cookies</h2>
            <p>Nous utilisons uniquement des cookies essentiels au fonctionnement du site (authentification, panier). Aucun cookie publicitaire tiers.</p>

            <h2>8. Contact</h2>
            <p>Pour toute question relative à vos données personnelles, contactez <strong>Africa Next Zone</strong> :</p>
            <ul>
                <li>Par e-mail : <strong><a href="mailto:owempay@icloud.com" class="text-emerald-700 font-semibold hover:underline">owempay@icloud.com</a></strong></li>
                <li>Par téléphone / WhatsApp : <strong><a href="tel:+237698595773" class="text-emerald-700 hover:underline">+237 698 595 773</a></strong> ou <strong><a href="tel:+237677797864" class="text-emerald-700 hover:underline">+237 677 797 864</a></strong></li>
            </ul>
            <p>Nous nous engageons à répondre sous 30 jours.</p>
        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500 mt-auto">
        &copy; {{ date('Y') }} Africa Next Zone — AgroNextZone, Marketplace Agricole du Cameroun.
    </footer>
</body>
</html>
