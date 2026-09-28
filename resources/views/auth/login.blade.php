<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | AgroMarcket</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-100 text-slate-800">
    <!-- Image de fond : remplacez le fichier public/images/auth-fond.jpg pour la changer. -->
    <div class="fixed inset-0 z-0">
        <img src="{{ asset('images/auth-fond.jpg') }}" alt="" class="h-full w-full object-cover" />
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-950/80 via-emerald-900/70 to-slate-900/80"></div>
    </div>
    <div class="relative z-10 min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg border border-emerald-100 p-8">
            <div class="mb-8 text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xl mb-4">A</div>
                <h1 class="text-3xl font-bold text-emerald-800">Connexion</h1>
                <p class="mt-2 text-sm text-slate-600">Accédez à votre espace AgroNextZone.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input id="email" name="email" type="email" required value="{{ old('email') }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Mot de passe</label>
                    <input id="password" name="password" type="password" required class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                </div>

                <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200">
                    Se connecter
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                Pas encore inscrit ?
                <a href="{{ route('register') }}" class="font-semibold text-emerald-700">Créer un compte</a>
            </p>
        </div>
    </div>
</body>
</html>
