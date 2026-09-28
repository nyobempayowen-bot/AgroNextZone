<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inscription | AgroNextZone</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step-panel {
            display: none;
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1), transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        .step-panel.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        /* Fond d'image par étape : DERRIÈRE le questionnaire (plein écran, la carte
           blanche reste par-dessus). Déposez public/images/step-N.jpg pour chaque étape.
           Une étape sans image garde le fond classique. */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0.35;
            pointer-events: none;
            z-index: -1;
        }
        body[data-step="1"]::before { background-image: url('{{ asset("images/step-1.jpg") }}'); }
        body[data-step="2"]::before { background-image: url('{{ asset("images/step-2.jpg") }}'); }
        body[data-step="3"]::before { background-image: url('{{ asset("images/step-3.jpg") }}'); }
        body[data-step="4"]::before { background-image: url('{{ asset("images/step-4.jpg") }}'); }
        body[data-step="5"]::before { background-image: url('{{ asset("images/step-5.jpg") }}'); }
        body[data-step="6"]::before { background-image: url('{{ asset("images/step-6.jpg") }}'); }
        body[data-step="7"]::before { background-image: url('{{ asset("images/step-7.jpg") }}'); }
        body[data-step="8"]::before { background-image: url('{{ asset("images/step-8.jpg") }}'); }
        .progress-bar-fill {
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body data-step="1" class="min-h-full text-slate-800 antialiased flex flex-col justify-between selection:bg-emerald-100 selection:text-emerald-900" style="background: #f8fafc;">

    <!-- Top Navigation Bar -->
    <header class="w-full border-b border-slate-200/80 bg-white/80 backdrop-blur-md sticky top-0 z-20">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 focus:outline-none focus:ring-2 focus:ring-emerald-600 rounded-xl">
                <div class="h-9 w-9 rounded-xl bg-emerald-700 text-white flex items-center justify-center font-black text-sm shadow-sm">
                    AN
                </div>
                <div class="flex flex-col">
                    <span class="text-base font-bold text-slate-900 leading-tight">AgroNextZone</span>
                    <span class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Plateforme Agricole</span>
                </div>
            </a>

            <div class="flex items-center gap-4 text-xs sm:text-sm">
                <span class="text-slate-500 hidden sm:inline">Vous avez déjà un compte ?</span>
                <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:text-emerald-800 px-3 py-1.5 rounded-lg hover:bg-emerald-50 transition">
                    Se connecter
                </a>
            </div>
        </div>
    </header>

    <!-- Main Registration Flow Container -->
    <main class="flex-1 max-w-2xl mx-auto w-full px-4 py-8 sm:py-12">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">

            <!-- Horizontal Progress Tracker -->
            <div class="bg-slate-50/70 border-b border-slate-100 p-4 sm:px-8 sm:py-5">
                <div class="flex items-center justify-between text-xs font-semibold text-slate-600 mb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-bold text-[11px]">
                            Étape <span id="label-current-step" class="ml-0.5">1</span> / 8
                        </span>
                        <span id="badge-role-tag" class="hidden text-[11px] px-2 py-0.5 rounded-md bg-slate-200 text-slate-700 font-medium"></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-slate-400">Progression :</span>
                        <span id="label-percentage" class="text-emerald-700 font-bold">12.5%</span>
                    </div>
                </div>

                <!-- Horizontal Segmented Progress Bar -->
                <div class="relative w-full bg-slate-200/80 rounded-full h-2 overflow-hidden">
                    <div id="registration-progress-bar" class="progress-bar-fill h-full bg-emerald-600 rounded-full" style="width: 12.5%;"></div>
                </div>

                <!-- Step Dots / Mini Steps for desktop -->
                <div class="hidden sm:grid grid-cols-8 gap-1 mt-3">
                    <div class="h-1 rounded-full bg-emerald-600 transition-colors" data-indicator-step="1"></div>
                    <div class="h-1 rounded-full bg-slate-200 transition-colors" data-indicator-step="2"></div>
                    <div class="h-1 rounded-full bg-slate-200 transition-colors" data-indicator-step="3"></div>
                    <div class="h-1 rounded-full bg-slate-200 transition-colors" data-indicator-step="4"></div>
                    <div class="h-1 rounded-full bg-slate-200 transition-colors" data-indicator-step="5"></div>
                    <div class="h-1 rounded-full bg-slate-200 transition-colors" data-indicator-step="6"></div>
                    <div class="h-1 rounded-full bg-slate-200 transition-colors" data-indicator-step="7"></div>
                    <div class="h-1 rounded-full bg-slate-200 transition-colors" data-indicator-step="8"></div>
                </div>
            </div>

            <!-- Content Body Area -->
            <div class="p-6 sm:p-8">

                <!-- Dynamic Step Header -->
                <div class="mb-6">
                    <h1 id="heading-step-title" class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                        Choisissez votre profil
                    </h1>
                    <p id="heading-step-desc" class="mt-1 text-sm text-slate-500">
                        Sélectionnez l'usage principal que vous ferez de la plateforme.
                    </p>
                </div>

                <!-- Global Error / Alert Box -->
                <div id="step-alert-box" class="hidden mb-6 rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-xs sm:text-sm text-rose-800 font-medium"></div>

                <!-- ========================================================================= -->
                <!-- ÉTAPE 1 : CHOIX DU RÔLE                                                   -->
                <!-- ========================================================================= -->
                <div id="panel-step-1" class="step-panel active">
                    <div class="space-y-3.5">
                        <!-- Option: Client (image de fond : public/images/auth-client.jpg) -->
                        <div data-role="client" class="relative overflow-hidden role-selector-card cursor-pointer rounded-xl border-2 border-slate-200 hover:border-emerald-600/60 bg-white p-4 sm:p-5 transition-all flex items-start gap-4 group">
                            <img src="{{ asset('images/auth-client.png') }}" alt=""
                                 class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-40 group-hover:opacity-55 transition-opacity" />
                            <div class="relative flex items-start gap-4 w-full">
                                <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center flex-shrink-0 border border-emerald-100 group-hover:scale-105 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm sm:text-base font-semibold text-slate-900">Acheteur / Consommateur</h3>
                                        <div class="radio-circle h-5 w-5 rounded-full border-2 border-slate-300 flex items-center justify-center">
                                            <div class="radio-dot h-2.5 w-2.5 rounded-full bg-emerald-600 hidden"></div>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                                        Commander des produits vivriers, fruits et légumes frais en direct des producteurs locaux camerounais.
                                    </p>
                                    <div class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-emerald-800">
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-100">Prix direct champ</span>
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-100">Chat avec producteur</span>
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-100">Suivi commande</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Option: Producteur (image de fond : public/images/auth-producteur.jpg) -->
                        <div data-role="producer" class="relative overflow-hidden role-selector-card cursor-pointer rounded-xl border-2 border-slate-200 hover:border-emerald-600/60 bg-white p-4 sm:p-5 transition-all flex items-start gap-4 group">
                            <img src="{{ asset('images/auth-producteur.jpg') }}" alt=""
                                 class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-40 group-hover:opacity-55 transition-opacity" />
                            <div class="relative flex items-start gap-4 w-full">
                                <div class="h-11 w-11 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center flex-shrink-0 border border-amber-100 group-hover:scale-105 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm sm:text-base font-semibold text-slate-900">Producteur / Agriculteur</h3>
                                        <div class="radio-circle h-5 w-5 rounded-full border-2 border-slate-300 flex items-center justify-center">
                                            <div class="radio-dot h-2.5 w-2.5 rounded-full bg-emerald-600 hidden"></div>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                                        Publier vos récoltes, gérer vos stocks, fixer vos prix et développer vos ventes auprès des familles et commerces.
                                    </p>
                                    <div class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-amber-900">
                                        <span class="px-2 py-0.5 rounded bg-amber-50 border border-amber-200/60">Gestion catalogue</span>
                                        <span class="px-2 py-0.5 rounded bg-amber-50 border border-amber-200/60">Visibilité locale & nationale</span>
                                        <span class="px-2 py-0.5 rounded bg-amber-50 border border-amber-200/60">Profil certifié</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 1 Footer Action -->
                    <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-end">
                        <button id="btn-submit-step-1" type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-800 active:bg-emerald-900 shadow-sm transition">
                            <span>Continuer</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- ÉTAPE 2 : INFORMATIONS PERSONNELLES                                       -->
                <!-- ========================================================================= -->
                <div id="panel-step-2" class="step-panel">
                    <form id="form-registration-step-2" enctype="multipart/form-data" class="space-y-4 sm:space-y-5" novalidate>

                        <!-- Photo de Profil (Upload Pro) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">Photo de profil</label>
                            <div class="flex items-center gap-4 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                <div class="relative h-16 w-16 rounded-full bg-slate-200 border-2 border-white shadow-sm flex items-center justify-center overflow-hidden flex-shrink-0">
                                    <svg id="avatar-fallback-icon" class="w-8 h-8 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                    <img id="avatar-preview" src="" alt="Aperçu photo" class="hidden h-full w-full object-cover" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <label for="input-profile-photo" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 shadow-xs transition">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            Choisir une image
                                        </label>
                                        <button id="btn-clear-photo" type="button" class="hidden text-xs text-rose-600 hover:underline font-medium">Retirer</button>
                                    </div>
                                    <input id="input-profile-photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden" />
                                    <p class="mt-1 text-[11px] text-slate-500">JPG, PNG ou WEBP (max. 2 Mo)</p>
                                    <p id="field-err-profile_photo" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Prénom & Nom -->
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="field-first_name" class="block text-xs font-semibold text-slate-700 mb-1">Prénom <span class="text-rose-500">*</span></label>
                                <input id="field-first_name" name="first_name" type="text" required placeholder="Ex: Paul" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                                <p id="field-err-first_name" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                            <div>
                                <label for="field-last_name" class="block text-xs font-semibold text-slate-700 mb-1">Nom <span class="text-rose-500">*</span></label>
                                <input id="field-last_name" name="last_name" type="text" required placeholder="Ex: Atangana" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                                <p id="field-err-last_name" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                        </div>

                        <!-- Sexe & Date de Naissance -->
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="field-gender" class="block text-xs font-semibold text-slate-700 mb-1">Genre <span class="text-rose-500">*</span></label>
                                <select id="field-gender" name="gender" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition">
                                    <option value="">Sélectionner</option>
                                    <option value="male">Homme</option>
                                    <option value="female">Femme</option>
                                    <option value="other">Autre / Non précisé</option>
                                </select>
                                <p id="field-err-gender" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                            <div>
                                <label for="field-date_of_birth" class="block text-xs font-semibold text-slate-700 mb-1">Date de naissance <span class="text-rose-500">*</span></label>
                                <input id="field-date_of_birth" name="date_of_birth" type="date" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                                <p class="mt-1 text-[11px] text-slate-500">Âge minimum : 16 ans</p>
                                <p id="field-err-date_of_birth" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                        </div>

                        <!-- Téléphone -->
                        <div>
                            <label for="field-phone" class="block text-xs font-semibold text-slate-700 mb-1">Numéro de téléphone <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-xs font-semibold text-slate-500">
                                    +237
                                </div>
                                <input id="field-phone" name="phone" type="tel" required placeholder="655 00 11 22" class="w-full rounded-xl border border-slate-300 pl-16 pr-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">Ce numéro servira pour valider votre compte et suivre vos commandes.</p>
                            <p id="field-err-phone" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                        </div>

                        <!-- Step 2 Actions -->
                        <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <button id="btn-back-from-2" type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span>Retour</span>
                            </button>
                            <button id="btn-submit-step-2" type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 shadow-sm transition">
                                <span>Continuer</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ========================================================================= -->
                <!-- ÉTAPE 3 : LOCALISATION & GÉOLOCALISATION                                   -->
                <!-- ========================================================================= -->
                <div id="panel-step-3" class="step-panel">
                    <form id="form-registration-step-3" class="space-y-4 sm:space-y-5" novalidate>

                        <!-- Pays & Région -->
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="field-country" class="block text-xs font-semibold text-slate-700 mb-1">Pays <span class="text-rose-500">*</span></label>
                                <input id="field-country" name="country" type="text" value="Cameroun" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                                <p id="field-err-country" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                            <div>
                                <label for="field-region" class="block text-xs font-semibold text-slate-700 mb-1">Région <span class="text-rose-500">*</span></label>
                                <select id="field-region" name="region" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition">
                                    <option value="">Sélectionner votre région</option>
                                    <option value="Adamaoua">Adamaoua</option>
                                    <option value="Centre">Centre</option>
                                    <option value="Est">Est</option>
                                    <option value="Extrême-Nord">Extrême-Nord</option>
                                    <option value="Littoral">Littoral</option>
                                    <option value="Nord">Nord</option>
                                    <option value="Nord-Ouest">Nord-Ouest</option>
                                    <option value="Ouest">Ouest</option>
                                    <option value="Sud">Sud</option>
                                    <option value="Sud-Ouest">Sud-Ouest</option>
                                </select>
                                <p id="field-err-region" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                        </div>

                        <!-- Ville & Localité -->
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="field-city" class="block text-xs font-semibold text-slate-700 mb-1">Ville <span class="text-rose-500">*</span></label>
                                <input id="field-city" name="city" type="text" required placeholder="Ex: Yaoundé, Douala, Bafia..." class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                                <p id="field-err-city" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                            <div>
                                <label for="field-locality" class="block text-xs font-semibold text-slate-700 mb-1">Localité / Quartier</label>
                                <input id="field-locality" name="locality" type="text" placeholder="Ex: Bastos, Bonabéri, Melen..." class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                                <p id="field-err-locality" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                        </div>

                        <!-- Géolocalisation interactive -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Coordonnées GPS
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Permet de calculer les producteurs à proximité et optimiser la logistique.</p>
                                </div>
                                <button id="btn-detect-gps" type="button" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-xs transition">
                                    <svg id="gps-action-icon" class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span id="gps-action-text">Détecter ma position</span>
                                </button>
                            </div>

                            <div id="gps-feedback-message" class="hidden mt-2 text-xs font-medium"></div>

                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label for="field-latitude" class="block text-[11px] font-medium text-slate-600 mb-1">Latitude</label>
                                    <input id="field-latitude" name="latitude" type="text" placeholder="3.8480" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-600 focus:outline-none transition" />
                                    <p id="field-err-latitude" class="field-error-msg mt-1 text-[11px] text-rose-600"></p>
                                </div>
                                <div>
                                    <label for="field-longitude" class="block text-[11px] font-medium text-slate-600 mb-1">Longitude</label>
                                    <input id="field-longitude" name="longitude" type="text" placeholder="11.5021" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800 focus:border-emerald-600 focus:outline-none transition" />
                                    <p id="field-err-longitude" class="field-error-msg mt-1 text-[11px] text-rose-600"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Champ spécifique Producteur : Localisation de l'exploitation -->
                        <div id="container-farm-location" class="hidden rounded-xl border border-amber-200 bg-amber-50/50 p-4">
                            <label for="field-farm_location" class="block text-xs font-semibold text-amber-950 mb-1">
                                Localisation de votre exploitation agricole <span class="text-amber-700 font-normal">(Champs / Ferme)</span>
                            </label>
                            <input id="field-farm_location" name="farm_location" type="text" placeholder="Ex: Plantation de Bafia, route Bokito Km 3" class="w-full rounded-xl border border-amber-300/80 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-amber-600 focus:ring-2 focus:ring-amber-600/20 focus:outline-none transition" />
                            <p class="mt-1 text-[11px] text-amber-800/80">
                                Cette indication sera utilisée pour afficher vos produits dans la recherche "Producteurs à proximité".
                            </p>
                            <p id="field-err-farm_location" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                        </div>

                        <!-- Step 3 Actions -->
                        <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <button id="btn-back-from-3" type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span>Retour</span>
                            </button>
                            <button id="btn-submit-step-3" type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 shadow-sm transition">
                                <span>Continuer</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ========================================================================= -->
                <!-- ÉTAPE 4 : INFORMATIONS PROFESSIONNELLES (PRODUCTEUR UNIQUEMENT)           -->
                <!-- ========================================================================= -->
                <div id="panel-step-4" class="step-panel">
                    <form id="form-registration-step-4" class="space-y-4 sm:space-y-5" novalidate>

                        <!-- Type d'activité & Spécialité -->
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="field-activity_type" class="block text-xs font-semibold text-slate-700 mb-1">Type d'activité <span class="text-rose-500">*</span></label>
                                <select id="field-activity_type" name="activity_type" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition">
                                    <option value="">Choisir votre filière</option>
                                    <option value="Cultures maraîchères & vivrières">Cultures maraîchères & vivrières</option>
                                    <option value="Cacao & Café (Rente)">Cacao & Café (Rente)</option>
                                    <option value="Arboriculture fruitière">Arboriculture fruitière</option>
                                    <option value="Élevage & Aviculture">Élevage & Aviculture</option>
                                    <option value="Pisciculture">Pisciculture</option>
                                    <option value="Transformation agricole">Transformation agricole</option>
                                    <option value="Autre">Autre activité agricole</option>
                                </select>
                                <p id="field-err-activity_type" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                            <div>
                                <label for="field-specialty" class="block text-xs font-semibold text-slate-700 mb-1">Spécialité principale</label>
                                <input id="field-specialty" name="specialty" type="text" placeholder="Ex: Cacao fermenté, Plantain bio..." class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                                <p id="field-err-specialty" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                        </div>

                        <!-- Produits principaux & Nom exploitation -->
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="field-main_products" class="block text-xs font-semibold text-slate-700 mb-1">Produits phares</label>
                                <input id="field-main_products" name="main_products" type="text" placeholder="Ex: Tomates, Piments, Avocats, Macabo" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                                <p id="field-err-main_products" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                            <div>
                                <label for="field-farm_name" class="block text-xs font-semibold text-slate-700 mb-1">Nom de l'exploitation / Ferme</label>
                                <input id="field-farm_name" name="farm_name" type="text" placeholder="Ex: Plantation Agro-Verte" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                                <p id="field-err-farm_name" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                            </div>
                        </div>

                        <!-- Années d'expérience -->
                        <div>
                            <label for="field-years_experience" class="block text-xs font-semibold text-slate-700 mb-1">Années d'expérience</label>
                            <input id="field-years_experience" name="years_experience" type="number" min="0" max="70" placeholder="Ex: 8" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                            <p id="field-err-years_experience" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                        </div>

                        <!-- Description / Bio -->
                        <div>
                            <label for="field-description" class="block text-xs font-semibold text-slate-700 mb-1">Présentation de votre production</label>
                            <textarea id="field-description" name="description" rows="3" placeholder="Présentez brièvement vos méthodes culturales (bio, raisonnée), vos engagements et votre histoire..." class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition"></textarea>
                            <p id="field-err-description" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                        </div>

                        <!-- Step 4 Actions -->
                        <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <button id="btn-back-from-4" type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span>Retour</span>
                            </button>
                            <button id="btn-submit-step-4" type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 shadow-sm transition">
                                <span>Continuer</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ========================================================================= -->
                <!-- ÉTAPE 5 : VÉRIFICATION D'IDENTITÉ CNI (PRODUCTEUR UNIQUEMENT)              -->
                <!-- ========================================================================= -->
                <div id="panel-step-5" class="step-panel">
                    <form id="form-registration-step-5" enctype="multipart/form-data" class="space-y-5" novalidate>

                        <!-- Numéro CNI -->
                        <div>
                            <label for="field-cni_number" class="block text-xs font-semibold text-slate-700 mb-1">Numéro de CNI / Passeport <span class="text-rose-500">*</span></label>
                            <input id="field-cni_number" name="cni_number" type="text" required placeholder="Ex: 1002938475" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                            <p id="field-err-cni_number" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                        </div>

                        <!-- Upload Document CNI -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Photo ou scan de la pièce d'identité <span class="text-rose-500">*</span></label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl bg-slate-50/50 hover:bg-slate-50 transition">
                                <div class="space-y-1 text-center">
                                    <div id="cni-icon-wrap" class="mx-auto h-12 w-12 text-slate-400 flex items-center justify-center">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div id="cni-preview-container" class="hidden my-2">
                                        <img id="cni-img-preview" src="" alt="Aperçu CNI" class="mx-auto max-h-36 rounded-lg object-contain border border-slate-200 shadow-xs" />
                                        <p id="cni-pdf-preview" class="hidden text-xs font-bold text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 inline-block"></p>
                                    </div>
                                    <div class="flex text-xs text-slate-600 justify-center">
                                        <label for="input-cni-document" class="relative cursor-pointer rounded-md font-semibold text-emerald-700 hover:text-emerald-800 focus-within:outline-none">
                                            <span>Téléverser un fichier</span>
                                            <input id="input-cni-document" name="cni_document" type="file" accept="image/jpeg,image/png,image/jpg,application/pdf" class="sr-only" />
                                        </label>
                                        <p class="pl-1">ou glisser-déposer</p>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Formats supportés : JPG, PNG ou PDF (max. 5 Mo)</p>
                                </div>
                            </div>
                            <p id="field-err-cni_document" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                        </div>

                        <!-- Security Note -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600 flex items-start gap-3">
                            <svg class="w-5 h-5 text-emerald-700 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <div>
                                <span class="font-bold text-slate-800 block">Stockage sécurisé & privé</span>
                                Ce document est chiffré dans un répertoire privé et inaccessible publiquement. Il sert exclusivement à la validation d'authenticité de votre profil producteur par nos administrateurs.
                            </div>
                        </div>

                        <!-- Step 5 Actions -->
                        <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <button id="btn-back-from-5" type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span>Retour</span>
                            </button>
                            <button id="btn-submit-step-5" type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 shadow-sm transition">
                                <span>Continuer</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ========================================================================= -->
                <!-- ÉTAPE 6 : SÉCURITÉ & CONNEXION                                            -->
                <!-- ========================================================================= -->
                <div id="panel-step-6" class="step-panel">
                    <form id="form-registration-step-6" class="space-y-4 sm:space-y-5" novalidate>

                        <!-- E-mail avec vérification asynchrone -->
                        <div>
                            <label for="field-email" class="block text-xs font-semibold text-slate-700 mb-1">Adresse e-mail <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input id="field-email" name="email" type="email" required placeholder="nom@exemple.cm" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                                <div id="email-live-status-icon" class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none hidden"></div>
                            </div>
                            <p id="email-live-status-text" class="mt-1 text-xs text-slate-500"></p>
                            <p id="field-err-email" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                        </div>

                        <!-- Mot de passe avec show/hide et indicateur de force -->
                        <div>
                            <label for="field-password" class="block text-xs font-semibold text-slate-700 mb-1">Mot de passe <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input id="field-password" name="password" type="password" required placeholder="••••••••" class="w-full rounded-xl border border-slate-300 pl-3.5 pr-10 py-2.5 text-sm text-slate-800 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                                <button id="btn-toggle-password" type="button" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                    <svg id="icon-show-pwd" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg id="icon-hide-pwd" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                </button>
                            </div>

                            <!-- Password strength progress indicator -->
                            <div class="mt-2">
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div id="pwd-strength-bar" class="h-full w-0 transition-all duration-300 rounded-full bg-rose-500"></div>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-slate-500 mt-1">
                                    <span id="pwd-strength-label">Minimum 8 caractères</span>
                                    <span id="pwd-criteria-text">Majuscule • Chiffre • Symbole</span>
                                </div>
                            </div>
                            <p id="field-err-password" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                        </div>

                        <!-- Confirmation du mot de passe -->
                        <div>
                            <label for="field-password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Confirmer le mot de passe <span class="text-rose-500">*</span></label>
                            <input id="field-password_confirmation" name="password_confirmation" type="password" required placeholder="••••••••" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                            <p id="field-err-password_confirmation" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                        </div>

                        <!-- CGU Checkbox -->
                        <div class="pt-2">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input id="field-accepted_terms" name="accepted_terms" type="checkbox" required class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mt-0.5" />
                                <span class="text-xs text-slate-600 leading-relaxed">
                                    J'accepte les <a href="#" class="font-semibold text-emerald-700 underline">Conditions Générales d'Utilisation</a> et la charte de transparence d'AgroNextZone.
                                </span>
                            </label>
                            <p id="field-err-accepted_terms" class="field-error-msg mt-1 text-xs text-rose-600"></p>
                        </div>

                        <!-- Step 6 Actions -->
                        <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <button id="btn-back-from-6" type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span>Retour</span>
                            </button>
                            <button id="btn-submit-step-6" type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 shadow-sm transition">
                                <span>Voir le récapitulatif</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ========================================================================= -->
                <!-- ÉTAPE 7 : RÉCAPITULATIF                                                    -->
                <!-- ========================================================================= -->
                <div id="panel-step-7" class="step-panel">
                    <div class="space-y-4">

                        <!-- Steps still incomplete, filled by the backend validation -->
                        <div id="recap-incomplete-box" class="hidden rounded-xl border-rose-200 bg-rose-50 p-4 text-xs sm:text-sm text-rose-800">
                            <p class="font-bold">Certaines informations obligatoires sont manquantes :</p>
                            <ul id="recap-incomplete-list" class="mt-2 list-disc list-inside space-y-1"></ul>
                            <p class="mt-2 text-[11px] text-rose-700">Complétez ces étapes pour recevoir votre code de vérification.</p>
                        </div>

                        <!-- Card Identité -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">1. Identité & Contact</span>
                                <button type="button" class="js-btn-edit-step text-xs font-semibold text-emerald-700 hover:underline" data-goto-step="2">Modifier</button>
                            </div>
                            <div class="mt-3 text-xs sm:text-sm grid grid-cols-2 gap-2 text-slate-700">
                                <div><span class="text-slate-400 block text-[11px]">Nom complet</span><strong id="recap-name">—</strong></div>
                                <div><span class="text-slate-400 block text-[11px]">Téléphone</span><strong id="recap-phone">—</strong></div>
                                <div><span class="text-slate-400 block text-[11px]">Date de naissance</span><span id="recap-dob">—</span></div>
                                <div><span class="text-slate-400 block text-[11px]">Genre</span><span id="recap-gender">—</span></div>
                            </div>
                        </div>

                        <!-- Card Localisation -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">2. Localisation</span>
                                <button type="button" class="js-btn-edit-step text-xs font-semibold text-emerald-700 hover:underline" data-goto-step="3">Modifier</button>
                            </div>
                            <div class="mt-3 text-xs sm:text-sm text-slate-700 space-y-1">
                                <div><span class="text-slate-400 text-[11px] inline-block w-20">Région / Ville</span><strong id="recap-location">—</strong></div>
                                <div><span class="text-slate-400 text-[11px] inline-block w-20">Quartier</span><span id="recap-locality">—</span></div>
                                <div id="recap-farm-box" class="hidden pt-1"><span class="text-slate-400 text-[11px] inline-block w-20">Exploitation</span><span id="recap-farm-val" class="font-medium text-amber-900">—</span></div>
                            </div>
                        </div>

                        <!-- Card Activité (Producteur) -->
                        <div id="recap-producer-activity-card" class="hidden rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">3. Activité Professionnelle</span>
                                <button type="button" class="js-btn-edit-step text-xs font-semibold text-emerald-700 hover:underline" data-goto-step="4">Modifier</button>
                            </div>
                            <div class="mt-3 text-xs sm:text-sm grid grid-cols-2 gap-2 text-slate-700">
                                <div><span class="text-slate-400 block text-[11px]">Type d'activité</span><strong id="recap-activity">—</strong></div>
                                <div><span class="text-slate-400 block text-[11px]">Spécialité</span><span id="recap-specialty">—</span></div>
                                <div><span class="text-slate-400 block text-[11px]">Produits phares</span><span id="recap-products">—</span></div>
                                <div><span class="text-slate-400 block text-[11px]">Nom exploitation</span><span id="recap-farm-name">—</span></div>
                            </div>
                        </div>

                        <!-- Card CNI (Producteur) -->
                        <div id="recap-producer-cni-card" class="hidden rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">4. Pièce d'identité (CNI)</span>
                                <button type="button" class="js-btn-edit-step text-xs font-semibold text-emerald-700 hover:underline" data-goto-step="5">Modifier</button>
                            </div>
                            <div class="mt-3 text-xs sm:text-sm text-slate-700 flex items-center justify-between">
                                <div><span class="text-slate-400 text-[11px] block">Numéro de pièce</span><strong id="recap-cni-num">—</strong></div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">En attente d'examen</span>
                            </div>
                        </div>

                        <!-- Card Sécurité -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Sécurité & Connexion</span>
                                <button type="button" class="js-btn-edit-step text-xs font-semibold text-emerald-700 hover:underline" data-goto-step="6">Modifier</button>
                            </div>
                            <div class="mt-3 text-xs sm:text-sm text-slate-700 flex items-center justify-between">
                                <div><span class="text-slate-400 text-[11px] block">Identifiant e-mail</span><strong id="recap-email">—</strong></div>
                                <span class="text-slate-400 text-xs">Mot de passe sécurisé (••••••••)</span>
                            </div>
                        </div>

                        <!-- Step 7 Actions -->
                        <div class="mt-8 pt-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3">
                            <button id="btn-back-from-7" type="button" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-300 px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                <span>Retour</span>
                            </button>
                            <button id="btn-submit-step-7" type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 sm:px-6 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-white hover:bg-emerald-800 shadow-sm transition">
                                <span>Valider et recevoir mon code</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- ÉTAPE 8 : CONFIRMATION OTP & CRÉATION DU COMPTE                           -->
                <!-- ========================================================================= -->
                <div id="panel-step-8" class="step-panel">
                    <form id="form-registration-step-8" class="space-y-6" novalidate>

                        <div class="text-center py-2">
                            <div class="mx-auto h-12 w-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">Vérification de sécurité</h3>
                            <p class="text-xs sm:text-sm text-slate-600 mt-1 max-w-sm mx-auto">
                                Saisissez le code à 6 chiffres transmis pour activer votre compte.
                            </p>
                        </div>

                        <!-- Discreet Testing Helper Notice -->
                        <div id="otp-dev-notice" class="hidden rounded-xl border border-emerald-200 bg-emerald-50/80 p-3 text-center text-xs font-semibold text-emerald-800">
                            Code de validation : <span id="otp-dev-code" class="font-mono text-sm tracking-widest text-emerald-950 font-black"></span>
                        </div>

                        <!-- 6-digit Code Input -->
                        <div>
                            <label for="field-otp" class="block text-xs font-semibold text-slate-700 text-center mb-2 uppercase tracking-wider">Code de confirmation</label>
                            <div class="max-w-xs mx-auto">
                                <input id="field-otp" name="otp" type="text" maxlength="6" pattern="[0-9]{6}" required placeholder="••••••" autocomplete="one-time-code" class="w-full text-center tracking-[0.5em] font-mono font-bold text-2xl rounded-xl border-2 border-slate-300 py-3 text-slate-900 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 focus:outline-none transition" />
                            </div>
                            <p id="field-err-otp" class="field-error-msg mt-1.5 text-center text-xs text-rose-600 font-medium"></p>
                        </div>

                        <!-- Resend timer -->
                        <div class="text-center text-xs text-slate-500">
                            <span id="otp-countdown-text">Renvoyer un nouveau code dans <strong id="otp-timer-seconds" class="text-slate-800">30s</strong></span>
                            <button id="btn-resend-otp" type="button" class="hidden font-bold text-emerald-700 hover:underline">Renvoyer un code</button>
                        </div>

                        <!-- Final Submit Button -->
                        <div class="pt-4 border-t border-slate-100">
                            <button id="btn-finalize-account" type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 py-3.5 px-6 text-sm font-bold text-white hover:bg-emerald-800 active:bg-emerald-900 shadow-sm transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Activer mon compte et terminer</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <!-- Page Footer -->
    <footer class="w-full py-6 text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} AgroNextZone Cameroun. Tous droits réservés.
    </footer>

    <!-- Interactive Client Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const TOTAL_STEPS = 8;
            let currentStep = 1;
            let chosenRole = sessionStorage.getItem('register.role') || '{{ session("register.role", "") }}' || null;

            // DOM Elements
            const labelCurrentStep = document.getElementById('label-current-step');
            const labelPercentage = document.getElementById('label-percentage');
            const progressBar = document.getElementById('registration-progress-bar');
            const stepTitle = document.getElementById('heading-step-title');
            const stepDesc = document.getElementById('heading-step-desc');
            const roleBadge = document.getElementById('badge-role-tag');
            const alertBox = document.getElementById('step-alert-box');
            const stepIndicators = document.querySelectorAll('[data-indicator-step]');

            // Step Metadata for header text
            const stepConfig = {
                1: { title: 'Choisissez votre profil', desc: 'Sélectionnez l\'usage principal que vous ferez de la plateforme.', percent: 12.5 },
                2: { title: 'Vos informations personnelles', desc: 'Renseignez votre nom, vos coordonnées et votre photo de profil.', percent: 25.0 },
                3: { title: 'Votre localisation', desc: 'Indiquez où vous êtes situé pour faciliter les échanges et les livraisons.', percent: 37.5 },
                4: { title: 'Informations professionnelles', desc: 'Présentez votre activité et votre exploitation agricole.', percent: 50.0 },
                5: { title: 'Vérification de votre compte', desc: 'Téléversez votre pièce d\'identité (CNI) pour sécuriser vos échanges.', percent: 62.5 },
                6: { title: 'Sécurité et connexion', desc: 'Créez vos identifiants pour sécuriser l\'accès à votre espace.', percent: 75.0 },
                7: { title: 'Récapitulatif de vos informations', desc: 'Vérifiez l\'exactitude de vos données avant validation.', percent: 87.5 },
                8: { title: 'Activation de votre compte', desc: 'Saisissez le code de validation reçu pour finaliser l\'inscription.', percent: 100.0 }
            };

            function showAlert(message, type = 'error') {
                alertBox.classList.remove('hidden', 'border-rose-200', 'bg-rose-50/80', 'text-rose-800', 'border-emerald-200', 'bg-emerald-50/80', 'text-emerald-800');
                if (type === 'error') {
                    alertBox.classList.add('border-rose-200', 'bg-rose-50/80', 'text-rose-800');
                } else {
                    alertBox.classList.add('border-emerald-200', 'bg-emerald-50/80', 'text-emerald-800');
                }
                alertBox.innerHTML = message;
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function hideAlert() {
                alertBox.classList.add('hidden');
                alertBox.innerHTML = '';
            }

            function clearErrors(form) {
                if (!form) return;
                form.querySelectorAll('.field-error-msg').forEach(el => el.textContent = '');
                form.querySelectorAll('.border-rose-500').forEach(el => {
                    el.classList.remove('border-rose-500', 'ring-2', 'ring-rose-500/20');
                    el.classList.add('border-slate-300');
                });
            }

            function renderErrors(form, errors) {
                clearErrors(form);
                let firstFailedField = null;
                for (const [field, msgs] of Object.entries(errors)) {
                    const errEl = document.getElementById('field-err-' + field);
                    const inputEl = form.querySelector(`[name="${field}"]`);
                    if (errEl) {
                        errEl.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
                    }
                    if (inputEl) {
                        inputEl.classList.remove('border-slate-300');
                        inputEl.classList.add('border-rose-500', 'ring-2', 'ring-rose-500/20');
                        if (!firstFailedField) firstFailedField = inputEl;
                    }
                }
                if (firstFailedField) {
                    firstFailedField.focus();
                }
            }

            function updateProgressDisplay(step) {
                const cfg = stepConfig[step] || { title: 'Inscription', desc: '', percent: (step / TOTAL_STEPS) * 100 };
                labelCurrentStep.textContent = step;
                labelPercentage.textContent = cfg.percent.toFixed(1) + '%';
                progressBar.style.width = cfg.percent + '%';

                stepTitle.textContent = cfg.title;
                stepDesc.textContent = cfg.desc;

                stepIndicators.forEach(ind => {
                    const indStep = parseInt(ind.getAttribute('data-indicator-step'), 10);
                    if (indStep <= step) {
                        ind.classList.remove('bg-slate-200');
                        ind.classList.add('bg-emerald-600');
                    } else {
                        ind.classList.remove('bg-emerald-600');
                        ind.classList.add('bg-slate-200');
                    }
                });

                if (chosenRole) {
                    roleBadge.classList.remove('hidden');
                    roleBadge.textContent = chosenRole === 'producer' ? 'Producteur' : 'Client';
                    const farmContainer = document.getElementById('container-farm-location');
                    if (farmContainer) {
                        if (chosenRole === 'producer') farmContainer.classList.remove('hidden');
                        else farmContainer.classList.add('hidden');
                    }
                } else {
                    roleBadge.classList.add('hidden');
                }
            }

            function navigateTo(step) {
                hideAlert();
                document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));

                const targetPanel = document.getElementById('panel-step-' + step);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }

                currentStep = step;
                document.body.dataset.step = String(step); // change le fond d'image DERRIÈRE le questionnaire
                updateProgressDisplay(currentStep);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            // =========================================================================
            // ÉTAPE 1 : CHOIX DU RÔLE
            // =========================================================================
            const roleCards = document.querySelectorAll('.role-selector-card');
            const btnSubmitStep1 = document.getElementById('btn-submit-step-1');

            function refreshRoleCardsUI() {
                roleCards.forEach(card => {
                    const cardRole = card.getAttribute('data-role');
                    const dot = card.querySelector('.radio-dot');
                    const circle = card.querySelector('.radio-circle');

                    if (cardRole === chosenRole) {
                        card.classList.add('border-emerald-600', 'bg-emerald-50/40', 'ring-2', 'ring-emerald-600/20');
                        card.classList.remove('border-slate-200', 'bg-white');
                        circle.classList.add('border-emerald-600');
                        circle.classList.remove('border-slate-300');
                        dot.classList.remove('hidden');
                    } else {
                        card.classList.remove('border-emerald-600', 'bg-emerald-50/40', 'ring-2', 'ring-emerald-600/20');
                        card.classList.add('border-slate-200', 'bg-white');
                        circle.classList.remove('border-emerald-600');
                        circle.classList.add('border-slate-300');
                        dot.classList.add('hidden');
                    }
                });
            }

            roleCards.forEach(card => {
                card.addEventListener('click', function () {
                    chosenRole = this.getAttribute('data-role');
                    sessionStorage.setItem('register.role', chosenRole);
                    refreshRoleCardsUI();
                    hideAlert();
                });
            });

            if (chosenRole) refreshRoleCardsUI();

            btnSubmitStep1.addEventListener('click', function () {
                if (!chosenRole) {
                    showAlert('Veuillez sélectionner un profil (Acheteur ou Producteur) pour continuer.');
                    return;
                }

                btnSubmitStep1.disabled = true;
                btnSubmitStep1.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Validation...';

                fetch('{{ route("register.step1") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ role: chosenRole })
                }).then(async res => {
                    btnSubmitStep1.disabled = false;
                    btnSubmitStep1.innerHTML = '<span>Continuer</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';

                    if (!res.ok) {
                        const payload = await res.json().catch(() => ({}));
                        showAlert((payload.errors && Object.values(payload.errors).flat().join('\n')) || 'Erreur lors de la sélection.');
                        return;
                    }

                    navigateTo(2);
                }).catch(() => {
                    btnSubmitStep1.disabled = false;
                    btnSubmitStep1.innerHTML = '<span>Continuer</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';
                    showAlert('Erreur réseau. Vérifiez votre connexion.');
                });
            });

            // =========================================================================
            // ÉTAPE 2 : INFORMATIONS PERSONNELLES
            // =========================================================================
            const formStep2 = document.getElementById('form-registration-step-2');
            const photoInput = document.getElementById('input-profile-photo');
            const avatarPreview = document.getElementById('avatar-preview');
            const avatarFallbackIcon = document.getElementById('avatar-fallback-icon');
            const btnClearPhoto = document.getElementById('btn-clear-photo');
            const btnBackFrom2 = document.getElementById('btn-back-from-2');
            const btnSubmitStep2 = document.getElementById('btn-submit-step-2');
            const dateOfBirthInput = document.getElementById('field-date_of_birth');

            (function () {
                if (dateOfBirthInput) {
                    const limit = new Date();
                    limit.setFullYear(limit.getFullYear() - 16);
                    dateOfBirthInput.max = `${limit.getFullYear()}-${String(limit.getMonth() + 1).padStart(2, '0')}-${String(limit.getDate()).padStart(2, '0')}`;
                }
            })();

            photoInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                if (!file.type.startsWith('image/')) {
                    showAlert('Le fichier sélectionné doit être une image (JPG, PNG ou WEBP).');
                    this.value = '';
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    showAlert('La photo ne doit pas dépasser 2 Mo.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    avatarPreview.src = e.target.result;
                    avatarPreview.classList.remove('hidden');
                    avatarFallbackIcon.classList.add('hidden');
                    btnClearPhoto.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            });

            btnClearPhoto.addEventListener('click', function () {
                photoInput.value = '';
                avatarPreview.src = '';
                avatarPreview.classList.add('hidden');
                avatarFallbackIcon.classList.remove('hidden');
                btnClearPhoto.classList.add('hidden');
            });

            btnBackFrom2.addEventListener('click', () => navigateTo(1));

            formStep2.addEventListener('submit', function (e) {
                e.preventDefault();
                clearErrors(formStep2);
                hideAlert();

                btnSubmitStep2.disabled = true;
                btnSubmitStep2.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Validation...';

                const formData = new FormData(formStep2);

                fetch('{{ route("register.step2") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: formData
                }).then(async res => {
                    btnSubmitStep2.disabled = false;
                    btnSubmitStep2.innerHTML = '<span>Continuer</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';

                    if (!res.ok) {
                        const payload = await res.json().catch(() => ({}));
                        if (payload.errors) renderErrors(formStep2, payload.errors);
                        else showAlert('Veuillez corriger les informations saisies.');
                        return;
                    }

                    try {
                        sessionStorage.setItem('register.step2', JSON.stringify({
                            first_name: formStep2.querySelector('#field-first_name')?.value || '',
                            last_name: formStep2.querySelector('#field-last_name')?.value || '',
                            gender: formStep2.querySelector('#field-gender')?.value || '',
                            date_of_birth: formStep2.querySelector('#field-date_of_birth')?.value || '',
                            phone: formStep2.querySelector('#field-phone')?.value || ''
                        }));
                    } catch (e) {}

                    navigateTo(3);
                }).catch(() => {
                    btnSubmitStep2.disabled = false;
                    btnSubmitStep2.innerHTML = '<span>Continuer</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';
                    showAlert('Erreur réseau. Veuillez réessayer.');
                });
            });

            // =========================================================================
            // ÉTAPE 3 : LOCALISATION & GÉOLOCALISATION
            // =========================================================================
            const formStep3 = document.getElementById('form-registration-step-3');
            const btnBackFrom3 = document.getElementById('btn-back-from-3');
            const btnSubmitStep3 = document.getElementById('btn-submit-step-3');
            const btnDetectGps = document.getElementById('btn-detect-gps');
            const gpsText = document.getElementById('gps-action-text');
            const gpsMsg = document.getElementById('gps-feedback-message');
            const latInput = document.getElementById('field-latitude');
            const lngInput = document.getElementById('field-longitude');

            btnBackFrom3.addEventListener('click', () => navigateTo(2));

            /**
             * Géocodage inverse : envoie lat/lng au serveur, qui interroge
             * Geoapify. La clé API reste côté serveur — le navigateur ne
             * contacte JAMAIS Geoapify directement.
             */
            async function reverseGeocode(lat, lng) {
                gpsText.textContent = 'Localisation...';
                gpsMsg.classList.remove('hidden', 'text-rose-600');
                gpsMsg.classList.add('text-emerald-700');
                gpsMsg.textContent = 'Recherche de votre ville...';

                try {
                    const res = await fetch("{{ route('geolocation.reverse') }}", {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ latitude: lat, longitude: lng })
                    });

                    const data = await res.json().catch(() => ({}));

                    if (!res.ok || !data.ok) {
                        gpsText.textContent = 'Detecter ma position';
                        gpsMsg.classList.remove('text-emerald-700');
                        gpsMsg.classList.add('text-rose-600');
                        gpsMsg.textContent = data.message
                            || 'Position detectee, mais ville introuvable. Saisissez-la manuellement.';
                        return;                    }

                    const p = data.place || {};

                    // Pré-remplit les champs pour que l'utilisateur puisse
                    // corriger avant de valider.
                    const sel = document.getElementById('field-region');
                    if (sel && p.region && !sel.value) {
                        const wanted = p.region.toLowerCase().trim();
                        const opt = Array.from(sel.options).find(function (o) {
                            const label = o.text.toLowerCase().trim();
                            return label === wanted
                                || label.indexOf(wanted) === 0
                                || wanted.indexOf(label) === 0;
                        });
                        if (opt) { sel.value = opt.value; }
                    }
                    if (p.city) {
                        document.getElementById('field-city').value = p.city;
                    }
                    if (p.locality) {
                        const loc = document.getElementById('field-locality');
                        if (loc && !loc.value) { loc.value = p.locality; }
                    }

                    const parts = [p.locality, p.city, p.region].filter(Boolean);
                    const unique = [...new Set(parts)];

                    gpsText.textContent = 'Position actualisee';                    gpsMsg.classList.remove('text-rose-600');
                    gpsMsg.classList.add('text-emerald-700');
                    gpsMsg.textContent = unique.length
                        ? 'Position detectee : ' + unique.join(', ') + '.'
                        : 'Position GPS detectee (' + lat + ', ' + lng + ').';

                } catch (err) {
                    gpsText.textContent = 'Detecter ma position';
                    gpsMsg.classList.remove('text-emerald-700');
                    gpsMsg.classList.add('text-rose-600');
                    gpsMsg.textContent = 'Service de localisation indisponible. Saisissez votre ville manuellement.';                }
            }

            btnDetectGps.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    gpsMsg.classList.remove('hidden', 'text-emerald-700');
                    gpsMsg.classList.add('text-rose-600');
                    gpsMsg.textContent = 'La géolocalisation n’est pas disponible sur cet appareil.';
                    return;
                }

                gpsText.textContent = 'Recherche...';
                gpsMsg.classList.remove('hidden', 'text-rose-600');
                gpsMsg.classList.add('text-emerald-700');
                gpsMsg.textContent = 'Récupération de vos coordonnées GPS...';

                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        const lat = pos.coords.latitude.toFixed(6);
                        const lng = pos.coords.longitude.toFixed(6);
                        latInput.value = lat;
                        lngInput.value = lng;
                        reverseGeocode(lat, lng);
                    },
                    function (err) {
                        gpsText.textContent = 'Détecter ma position';
                        gpsMsg.classList.remove('text-emerald-700');
                        gpsMsg.classList.add('text-rose-600');
                        gpsMsg.textContent = err.code === 1
                            ? 'Accès GPS refusé. Autorisez la localisation ou saisissez votre ville.'
                            : 'Impossible d\'obtenir la position. Saisissez votre ville manuellement.';
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            });

            formStep3.addEventListener('submit', function (e) {
                e.preventDefault();
                clearErrors(formStep3);
                hideAlert();

                btnSubmitStep3.disabled = true;
                btnSubmitStep3.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Validation...';

                const formData = new FormData(formStep3);

                fetch('{{ route("register.step3") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: formData
                }).then(async res => {
                    btnSubmitStep3.disabled = false;
                    btnSubmitStep3.innerHTML = '<span>Continuer</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';

                    if (!res.ok) {
                        const payload = await res.json().catch(() => ({}));
                        if (payload.errors) renderErrors(formStep3, payload.errors);
                        else showAlert('Veuillez vérifier les informations de localisation.');
                        return;
                    }

                    try {
                        sessionStorage.setItem('register.step3', JSON.stringify({
                            country: formStep3.querySelector('#field-country')?.value || '',
                            region: formStep3.querySelector('#field-region')?.value || '',
                            city: formStep3.querySelector('#field-city')?.value || '',
                            locality: formStep3.querySelector('#field-locality')?.value || '',
                            latitude: formStep3.querySelector('#field-latitude')?.value || '',
                            longitude: formStep3.querySelector('#field-longitude')?.value || '',
                            farm_location: formStep3.querySelector('#field-farm_location')?.value || ''
                        }));
                    } catch (e) {}

                    // Routing based on role:
                    // Client skips Steps 4 & 5 directly to Step 6!
                    if (chosenRole === 'producer') {
                        navigateTo(4);
                    } else {
                        navigateTo(6);
                    }
                }).catch(() => {
                    btnSubmitStep3.disabled = false;
                    btnSubmitStep3.innerHTML = '<span>Continuer</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';
                    showAlert('Erreur réseau. Veuillez vérifier votre connexion.');
                });
            });

            // =========================================================================
            // ÉTAPE 4 : INFORMATIONS PROFESSIONNELLES (PRODUCTEUR)
            // =========================================================================
            const formStep4 = document.getElementById('form-registration-step-4');
            const btnBackFrom4 = document.getElementById('btn-back-from-4');
            const btnSubmitStep4 = document.getElementById('btn-submit-step-4');

            btnBackFrom4.addEventListener('click', () => navigateTo(3));

            formStep4.addEventListener('submit', function (e) {
                e.preventDefault();
                clearErrors(formStep4);
                hideAlert();

                btnSubmitStep4.disabled = true;
                btnSubmitStep4.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Validation...';

                const formData = new FormData(formStep4);

                fetch('{{ route("register.step4") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: formData
                }).then(async res => {
                    btnSubmitStep4.disabled = false;
                    btnSubmitStep4.innerHTML = '<span>Continuer</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';

                    if (!res.ok) {
                        const payload = await res.json().catch(() => ({}));
                        if (payload.errors) renderErrors(formStep4, payload.errors);
                        else showAlert('Veuillez vérifier les informations professionnelles.');
                        return;
                    }

                    try {
                        sessionStorage.setItem('register.step4', JSON.stringify({
                            activity_type: formStep4.querySelector('#field-activity_type')?.value || '',
                            specialty: formStep4.querySelector('#field-specialty')?.value || '',
                            main_products: formStep4.querySelector('#field-main_products')?.value || '',
                            farm_name: formStep4.querySelector('#field-farm_name')?.value || '',
                            years_experience: formStep4.querySelector('#field-years_experience')?.value || '',
                            description: formStep4.querySelector('#field-description')?.value || ''
                        }));
                    } catch (e) {}

                    navigateTo(5);
                }).catch(() => {
                    btnSubmitStep4.disabled = false;
                    btnSubmitStep4.innerHTML = '<span>Continuer</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';
                    showAlert('Erreur réseau. Veuillez vérifier votre connexion.');
                });
            });

            // =========================================================================
            // ÉTAPE 5 : VÉRIFICATION CNI (PRODUCTEUR)
            // =========================================================================
            const formStep5 = document.getElementById('form-registration-step-5');
            const cniInput = document.getElementById('input-cni-document');
            const cniPreviewContainer = document.getElementById('cni-preview-container');
            const cniImgPreview = document.getElementById('cni-img-preview');
            const cniPdfPreview = document.getElementById('cni-pdf-preview');
            const cniIconWrap = document.getElementById('cni-icon-wrap');
            const btnBackFrom5 = document.getElementById('btn-back-from-5');
            const btnSubmitStep5 = document.getElementById('btn-submit-step-5');

            cniInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                if (file.size > 5 * 1024 * 1024) {
                    showAlert('Le document de CNI ne doit pas dépasser 5 Mo.');
                    this.value = '';
                    return;
                }

                cniPreviewContainer.classList.remove('hidden');
                cniIconWrap.classList.add('hidden');

                if (file.type === 'application/pdf') {
                    cniImgPreview.classList.add('hidden');
                    cniPdfPreview.classList.remove('hidden');
                    cniPdfPreview.textContent = '[PDF] Fichier chargé : ' + file.name;
                } else if (file.type.startsWith('image/')) {
                    cniPdfPreview.classList.add('hidden');
                    cniImgPreview.classList.remove('hidden');
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        cniImgPreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            btnBackFrom5.addEventListener('click', () => navigateTo(4));

            formStep5.addEventListener('submit', function (e) {
                e.preventDefault();
                clearErrors(formStep5);
                hideAlert();

                btnSubmitStep5.disabled = true;
                btnSubmitStep5.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Envoi sécurisé...';

                const formData = new FormData(formStep5);

                fetch('{{ route("register.step5") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: formData
                }).then(async res => {
                    btnSubmitStep5.disabled = false;
                    btnSubmitStep5.innerHTML = '<span>Continuer</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';

                    if (!res.ok) {
                        const payload = await res.json().catch(() => ({}));
                        if (payload.errors) renderErrors(formStep5, payload.errors);
                        else showAlert('Veuillez vérifier les informations de la pièce d’identité.');
                        return;
                    }

                    try {
                        sessionStorage.setItem('register.step5', JSON.stringify({
                            cni_number: formStep5.querySelector('#field-cni_number')?.value || ''
                        }));
                    } catch (e) {}

                    navigateTo(6);
                }).catch(() => {
                    btnSubmitStep5.disabled = false;
                    btnSubmitStep5.innerHTML = '<span>Continuer</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';
                    showAlert('Erreur réseau. Veuillez réessayer.');
                });
            });

            // =========================================================================
            // ÉTAPE 6 : SÉCURITÉ & CONNEXION
            // =========================================================================
            const formStep6 = document.getElementById('form-registration-step-6');
            const emailInput = document.getElementById('field-email');
            const emailLiveIcon = document.getElementById('email-live-status-icon');
            const emailLiveText = document.getElementById('email-live-status-text');
            const pwdInput = document.getElementById('field-password');
            const pwdConfirmInput = document.getElementById('field-password_confirmation');
            const btnTogglePwd = document.getElementById('btn-toggle-password');
            const iconShowPwd = document.getElementById('icon-show-pwd');
            const iconHidePwd = document.getElementById('icon-hide-pwd');
            const pwdStrengthBar = document.getElementById('pwd-strength-bar');
            const pwdStrengthLabel = document.getElementById('pwd-strength-label');
            const btnBackFrom6 = document.getElementById('btn-back-from-6');
            const btnSubmitStep6 = document.getElementById('btn-submit-step-6');

            // Async Email Check
            let emailCheckTimeout = null;
            emailInput.addEventListener('input', function () {
                clearTimeout(emailCheckTimeout);
                const val = this.value.trim();
                if (!val || !val.includes('@')) {
                    emailLiveIcon.classList.add('hidden');
                    emailLiveText.textContent = '';
                    return;
                }

                emailCheckTimeout = setTimeout(() => {
                    fetch('{{ route("register.checkEmail") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ email: val })
                    }).then(r => r.json()).then(payload => {
                        emailLiveIcon.classList.remove('hidden');
                        if (payload.available) {
                            emailLiveIcon.innerHTML = '<svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>';
                            emailLiveText.className = 'mt-1 text-xs text-emerald-700 font-medium';
                            emailLiveText.textContent = 'Adresse e-mail disponible.';
                        } else {
                            emailLiveIcon.innerHTML = '<svg class="w-4 h-4 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>';
                            emailLiveText.className = 'mt-1 text-xs text-rose-600 font-medium';
                            emailLiveText.innerHTML = payload.message + ' <a href="{{ route("login") }}" class="underline font-bold text-emerald-700 ml-1">Se connecter</a>';
                        }
                    }).catch(() => {});
                }, 400);
            });

            // Toggle Password Visibility
            btnTogglePwd.addEventListener('click', function () {
                if (pwdInput.type === 'password') {
                    pwdInput.type = 'text';
                    pwdConfirmInput.type = 'text';
                    iconShowPwd.classList.add('hidden');
                    iconHidePwd.classList.remove('hidden');
                } else {
                    pwdInput.type = 'password';
                    pwdConfirmInput.type = 'password';
                    iconShowPwd.classList.remove('hidden');
                    iconHidePwd.classList.add('hidden');
                }
            });

            // Password Strength Indicator
            pwdInput.addEventListener('input', function () {
                const val = this.value;
                let score = 0;
                if (val.length >= 8) score += 25;
                if (/[A-Z]/.test(val)) score += 25;
                if (/[0-9]/.test(val)) score += 25;
                if (/[^A-Za-z0-9]/.test(val)) score += 25;

                pwdStrengthBar.style.width = score + '%';
                pwdStrengthBar.classList.remove('bg-rose-500', 'bg-amber-500', 'bg-emerald-500', 'bg-emerald-600');

                if (score <= 25) {
                    pwdStrengthBar.classList.add('bg-rose-500');
                    pwdStrengthLabel.textContent = 'Mot de passe faible (trop court)';
                } else if (score <= 50) {
                    pwdStrengthBar.classList.add('bg-amber-500');
                    pwdStrengthLabel.textContent = 'Moyen (ajoutez chiffres/symboles)';
                } else if (score <= 75) {
                    pwdStrengthBar.classList.add('bg-emerald-500');
                    pwdStrengthLabel.textContent = 'Bon niveau de sécurité';
                } else {
                    pwdStrengthBar.classList.add('bg-emerald-600');
                    pwdStrengthLabel.textContent = 'Mot de passe très sécurisé';
                }
            });

            btnBackFrom6.addEventListener('click', function () {
                if (chosenRole === 'producer') navigateTo(5);
                else navigateTo(3);
            });

            formStep6.addEventListener('submit', function (e) {
                e.preventDefault();
                clearErrors(formStep6);
                hideAlert();

                btnSubmitStep6.disabled = true;
                btnSubmitStep6.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Validation...';

                const formData = new FormData(formStep6);

                fetch('{{ route("register.step6") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: formData
                }).then(async res => {
                    btnSubmitStep6.disabled = false;
                    btnSubmitStep6.innerHTML = '<span>Voir le récapitulatif</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';

                    const payload6 = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        if (payload6.blocked_step) {
                            showAlert(payload6.errors?.step?.[0] || 'Une étape précédente est incomplète.');
                            navigateTo(payload6.blocked_step);
                            return;
                        }
                        if (payload6.errors) renderErrors(formStep6, payload6.errors);
                        else showAlert('Veuillez corriger les informations de connexion.');
                        return;
                    }

                    // Server confirmed the step is stored: keep the values locally too
                    // so going back later does not wipe what the user typed.
                    try {
                        sessionStorage.setItem('register.step6', JSON.stringify({
                            email: payload6.email || ''
                        }));
                    } catch (e) {}

                    populateRecapData();
                    navigateTo(7);
                }).catch(() => {
                    btnSubmitStep6.disabled = false;
                    btnSubmitStep6.innerHTML = '<span>Voir le récapitulatif</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';
                    showAlert('Erreur réseau. Veuillez réessayer.');
                });
            });

            // =========================================================================
            // ÉTAPE 7 : RÉCAPITULATIF INTERACTIF
            // =========================================================================
            const btnBackFrom7 = document.getElementById('btn-back-from-7');
            const btnSubmitStep7 = document.getElementById('btn-submit-step-7');

            function populateRecapData() {
                fetch('{{ route("register.recap") }}', {
                    headers: { 'Accept': 'application/json' }
                }).then(r => r.json()).then(data => {
                    const s2 = data.step2 || {};
                    const s3 = data.step3 || {};
                    const s4 = data.step4 || {};
                    const s5 = data.step5 || {};
                    const s6 = data.step6 || {};

                    // 1. Identity
                    const firstName = s2.first_name || document.getElementById('field-first_name')?.value || '';
                    const lastName = s2.last_name || document.getElementById('field-last_name')?.value || '';
                    document.getElementById('recap-name').textContent = `${firstName} ${lastName}`.trim() || '—';
                    document.getElementById('recap-phone').textContent = s2.phone || document.getElementById('field-phone')?.value || '—';
                    document.getElementById('recap-dob').textContent = s2.date_of_birth || document.getElementById('field-date_of_birth')?.value || '—';
                    const genderMap = { 'male': 'Homme', 'female': 'Femme', 'other': 'Autre' };
                    document.getElementById('recap-gender').textContent = genderMap[s2.gender || document.getElementById('field-gender')?.value] || '—';

                    // 2. Location
                    const region = s3.region || document.getElementById('field-region')?.value || '';
                    const city = s3.city || document.getElementById('field-city')?.value || '';
                    document.getElementById('recap-location').textContent = `${city} (${region})` || '—';
                    document.getElementById('recap-locality').textContent = s3.locality || document.getElementById('field-locality')?.value || '—';

                    const farmLoc = s3.farm_location || document.getElementById('field-farm_location')?.value;
                    const recapFarmBox = document.getElementById('recap-farm-box');
                    if (chosenRole === 'producer' && farmLoc) {
                        recapFarmBox.classList.remove('hidden');
                        document.getElementById('recap-farm-val').textContent = farmLoc;
                    } else {
                        recapFarmBox.classList.add('hidden');
                    }

                    // 3. Producer Professional Info
                    const recapActivityCard = document.getElementById('recap-producer-activity-card');
                    if (chosenRole === 'producer') {
                        recapActivityCard.classList.remove('hidden');
                        document.getElementById('recap-activity').textContent = s4.activity_type || document.getElementById('field-activity_type')?.value || '—';
                        document.getElementById('recap-specialty').textContent = s4.specialty || document.getElementById('field-specialty')?.value || '—';
                        document.getElementById('recap-products').textContent = s4.main_products || document.getElementById('field-main_products')?.value || '—';
                        document.getElementById('recap-farm-name').textContent = s4.farm_name || document.getElementById('field-farm_name')?.value || '—';
                    } else {
                        recapActivityCard.classList.add('hidden');
                    }

                    // 4. Producer CNI
                    const recapCniCard = document.getElementById('recap-producer-cni-card');
                    if (chosenRole === 'producer') {
                        recapCniCard.classList.remove('hidden');
                        document.getElementById('recap-cni-num').textContent = s5.cni_number || document.getElementById('field-cni_number')?.value || '—';
                    } else {
                        recapCniCard.classList.add('hidden');
                    }

                    // 5. Security — email comes from the SERVER payload only (single source of truth).
                    document.getElementById('recap-email').textContent = s6.email || '—';

                    // 6. Surface any incomplete step reported by the backend.
                    renderIncompleteSteps(data.incomplete_steps || []);
                }).catch(() => {});
            }

            // Backend-driven recap guard: an incomplete step blocks the OTP request.
            function renderIncompleteSteps(steps) {
                const box = document.getElementById('recap-incomplete-box');
                const btn = document.getElementById('btn-submit-step-7');
                const list = document.getElementById('recap-incomplete-list');
                if (!box || !list || !btn) return;

                list.innerHTML = '';

                if (!steps.length) {
                    box.classList.add('hidden');
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    return;
                }

                steps.forEach(function (s) {
                    const li = document.createElement('li');                    
                    const link = document.createElement('button');
                    link.type = 'button';
                    link.className = 'underline font-bold text-rose-800 hover:text-rose-950 js-btn-edit-step';
                    link.setAttribute('data-goto-step', String(s.step));
                    link.textContent = 'Étape ' + s.step + ' — ' + s.label;
                    link.addEventListener('click', function () {
                        navigateTo(parseInt(this.getAttribute('data-goto-step'), 10));
                    });
                    li.appendChild(link);
                    li.appendChild(document.createTextNode(' : informations manquantes.'));
                    list.appendChild(li);
                });

                box.classList.remove('hidden');
            }

            // Quick Edit Buttons in Recap
            document.querySelectorAll('.js-btn-edit-step').forEach(btn => {
                btn.addEventListener('click', function () {
                    const stepNum = parseInt(this.getAttribute('data-goto-step'), 10);
                    if (stepNum) navigateTo(stepNum);
                });
            });

            btnBackFrom7.addEventListener('click', () => navigateTo(6));

            btnSubmitStep7.addEventListener('click', function () {
                btnSubmitStep7.disabled = true;
                btnSubmitStep7.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Envoi du code...';

                fetch('{{ route("register.sendOtp") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                }).then(async res => {
                    btnSubmitStep7.disabled = false;
                    btnSubmitStep7.innerHTML = '<span>Valider et recevoir mon code</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';

                    if (!res.ok) {
                        const payload = await res.json().catch(() => ({}));
                        const msg = (payload.errors && Object.values(payload.errors).flat().join(' — '))
                            || 'Impossible d\'envoyer le code de confirmation.';
                        showAlert(msg);

                        // The backend tells us exactly which step is incomplete.
                        if (payload.blocked_step) {
                            populateRecapData();
                            navigateTo(parseInt(payload.blocked_step, 10));
                        }
                        return;
                    }

                    const payload = await res.json();
                    if (payload.dev_code) {
                        const devNotice = document.getElementById('otp-dev-notice');
                        const devCode = document.getElementById('otp-dev-code');
                        devNotice.classList.remove('hidden');
                        devCode.textContent = payload.dev_code;
                    }

                    startOtpCountdown();
                    navigateTo(8);
                }).catch(() => {
                    btnSubmitStep7.disabled = false;
                    btnSubmitStep7.innerHTML = '<span>Valider et recevoir mon code</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';
                    showAlert('Erreur réseau lors de l\'envoi du code.');
                });
            });

            // =========================================================================
            // ÉTAPE 8 : CONFIRMATION OTP & FINALISATION
            // =========================================================================
            const formStep8 = document.getElementById('form-registration-step-8');
            const otpInput = document.getElementById('field-otp');
            const btnResendOtp = document.getElementById('btn-resend-otp');
            const otpCountdownText = document.getElementById('otp-countdown-text');
            const otpTimerSeconds = document.getElementById('otp-timer-seconds');
            const btnFinalizeAccount = document.getElementById('btn-finalize-account');
            let countdownInterval = null;

            function startOtpCountdown() {
                let seconds = 30;
                otpCountdownText.classList.remove('hidden');
                btnResendOtp.classList.add('hidden');
                otpTimerSeconds.textContent = seconds + 's';

                clearInterval(countdownInterval);
                countdownInterval = setInterval(() => {
                    seconds--;
                    if (seconds <= 0) {
                        clearInterval(countdownInterval);
                        otpCountdownText.classList.add('hidden');
                        btnResendOtp.classList.remove('hidden');
                    } else {
                        otpTimerSeconds.textContent = seconds + 's';
                    }
                }, 1000);
            }

            btnResendOtp.addEventListener('click', function () {
                btnResendOtp.disabled = true;
                btnResendOtp.textContent = 'Envoi...';

                fetch('{{ route("register.resendOtp") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                }).then(async res => {
                    btnResendOtp.disabled = false;
                    btnResendOtp.textContent = 'Renvoyer un code';

                    if (res.ok) {
                        const payload = await res.json();
                        if (payload.dev_code) {
                            document.getElementById('otp-dev-code').textContent = payload.dev_code;
                        }
                        startOtpCountdown();
                        showAlert('Un nouveau code vous a été transmis.', 'success');
                    }
                }).catch(() => {
                    btnResendOtp.disabled = false;
                    btnResendOtp.textContent = 'Renvoyer un code';
                });
            });

            formStep8.addEventListener('submit', function (e) {
                e.preventDefault();
                clearErrors(formStep8);
                hideAlert();

                const otpVal = otpInput.value.trim();
                if (!otpVal || otpVal.length !== 6) {
                    renderErrors(formStep8, { otp: 'Veuillez saisir un code valide à 6 chiffres.' });
                    return;
                }

                btnFinalizeAccount.disabled = true;
                btnFinalizeAccount.innerHTML = '<span class="inline-block animate-spin mr-2">⏳</span> Activation de votre compte...';

                fetch('{{ route("register.step8") }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ otp: otpVal })
                }).then(async res => {
                    if (!res.ok) {
                        btnFinalizeAccount.disabled = false;
                        btnFinalizeAccount.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>Activer mon compte et terminer</span>';
                        const payload = await res.json().catch(() => ({}));
                        if (payload.errors) renderErrors(formStep8, payload.errors);
                        else showAlert('Code invalide ou erreur lors de l\'activation.');
                        return;
                    }

                    const payload = await res.json();
                    showAlert('Félicitations ! Votre compte a été activé avec succès. Redirection en cours...', 'success');
                    sessionStorage.clear();

                    setTimeout(() => {
                        window.location.href = payload.redirect || '{{ route("home") }}';
                    }, 1000);
                }).catch(() => {
                    btnFinalizeAccount.disabled = false;
                    btnFinalizeAccount.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>Activer mon compte et terminer</span>';
                    showAlert('Erreur de communication avec le serveur.');
                });
            });

            // Restore initial values from sessionStorage
            (function () {
                try {
                    const s2 = JSON.parse(sessionStorage.getItem('register.step2') || 'null');
                    if (s2) {
                        if (s2.first_name) document.getElementById('field-first_name').value = s2.first_name;
                        if (s2.last_name) document.getElementById('field-last_name').value = s2.last_name;
                        if (s2.gender) document.getElementById('field-gender').value = s2.gender;
                        if (s2.date_of_birth) document.getElementById('field-date_of_birth').value = s2.date_of_birth;
                        if (s2.phone) document.getElementById('field-phone').value = s2.phone;
                    }
                    const s3 = JSON.parse(sessionStorage.getItem('register.step3') || 'null');
                    if (s3) {
                        if (s3.country) document.getElementById('field-country').value = s3.country;
                        if (s3.region) document.getElementById('field-region').value = s3.region;
                        if (s3.city) document.getElementById('field-city').value = s3.city;
                        if (s3.locality) document.getElementById('field-locality').value = s3.locality;
                        if (s3.latitude) document.getElementById('field-latitude').value = s3.latitude;
                        if (s3.longitude) document.getElementById('field-longitude').value = s3.longitude;
                        if (s3.farm_location) document.getElementById('field-farm_location').value = s3.farm_location;
                    }
                    const s4 = JSON.parse(sessionStorage.getItem('register.step4') || 'null');
                    if (s4) {
                        if (s4.activity_type) document.getElementById('field-activity_type').value = s4.activity_type;
                        if (s4.specialty) document.getElementById('field-specialty').value = s4.specialty;
                        if (s4.main_products) document.getElementById('field-main_products').value = s4.main_products;
                        if (s4.farm_name) document.getElementById('field-farm_name').value = s4.farm_name;
                        if (s4.years_experience) document.getElementById('field-years_experience').value = s4.years_experience;
                        if (s4.description) document.getElementById('field-description').value = s4.description;
                    }
                    const s5 = JSON.parse(sessionStorage.getItem('register.step5') || 'null');
                    if (s5 && s5.cni_number) {
                        document.getElementById('field-cni_number').value = s5.cni_number;
                    }

                    if (s6 && s6.email) {
                        const emailField = document.getElementById('field-email');
                        if (emailField && !emailField.value) emailField.value = s6.email;
                    }
                    const s6 = JSON.parse(sessionStorage.getItem('register.step6') || 'null');
                    if (s6 && s6.email) {
                        document.getElementById('field-email').value = s6.email;
                    }
                } catch (e) {}
            })();
        });
    </script>
</body>
</html>
