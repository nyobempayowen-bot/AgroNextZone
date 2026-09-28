<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Bloc C — Google Maps Platform (Geocoding API + Maps JavaScript API).
    | La clé DOIT être définie dans .env (GOOGLE_MAPS_API_KEY=...) et restreinte
    | dans Google Cloud Console : referrer HTTP pour le JS côté navigateur,
    | restriction par IP pour les appels serveur (Geocoding).
    */
    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    /*
    | Geoapify — geocodage et geocodage inverse (serveur uniquement).
    |
    | La cle vit dans .env (GEOAPIFY_API_KEY=...) et n'est JAMAIS envoyee au
    | navigateur : le JavaScript appelle les routes Laravel /geolocation/*,
    | qui interrogent Geoapify cote serveur. Aucun appel direct depuis le
    | front, donc aucune cle visible dans le code public.
    */
    'geoapify' => [
        'key' => env('GEOAPIFY_API_KEY'),
        'base_url' => rtrim((string) env('GEOAPIFY_BASE_URL', 'https://api.geoapify.com/v1'), '/'),
        'timeout' => (int) env('GEOAPIFY_TIMEOUT', 10),
        'connect_timeout' => (int) env('GEOAPIFY_CONNECT_TIMEOUT', 5),
        // Les resultats de geocodage sont stables : on les cache 30 jours.
        'cache_ttl' => (int) env('GEOAPIFY_CACHE_TTL', 60 * 24 * 30),
    ],

    /*
    | Bloc C (2/2) — Fournisseur IA des recommandations et de l'assistant repas.
    |
    | OpenRouter est désormais le SEUL fournisseur IA utilisé par l'application.
    | L'API est compatible OpenAI : POST {base}/chat/completions, clé envoyée
    | dans l'en-tête Authorization: Bearer (JAMAIS dans le corps, JAMAIS en JS/Blade).
    |
    | La clé DOIT être définie dans .env (OPENROUTER_API_KEY=...). Elle ne quitte
    | jamais le serveur : tous les appels passent par le backend Laravel.
    |
    | Les clés de configuration historiques (cache_ttl, timeout, max_offers) sont
    | conservées ici : elles pilotent le comportement métier d'AgroNextZone
    | indépendamment du fournisseur, et sont partagées avec l'ancien bloc `gemini`
    | laissé en place tant que le geocoding Google (fonction distincte) l'utilise.
    */
    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'base_url' => rtrim((string) env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'), '/'),
        // Routeur gratuit d'OpenRouter : sélectionne automatiquement un modèle gratuit.
        'model' => env('OPENROUTER_MODEL', 'openrouter/free'),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 60),
        'connect_timeout' => (int) env('OPENROUTER_CONNECT_TIMEOUT', 10),
        'cache_ttl' => (int) env('OPENROUTER_CACHE_TTL', 45), // minutes
        'max_offers' => (int) env('OPENROUTER_MAX_OFFERS', 40),
        // Nom et URL affichés à OpenRouter pour le tableau de bord du compte.
        'app_name' => env('OPENROUTER_APP_NAME', 'AgroNextZone'),
        'app_url' => env('OPENROUTER_APP_URL', env('APP_URL', 'http://localhost')),
    ],

    /*
    | Bloc C (2/2) — Google Gemini.
    |
    | CONSERVÉ TEMPORAIREMENT : utilisé uniquement par le Geocoding Google Maps
    | (app/Services/GeocodingService.php, clé GOOGLE_MAPS_API_KEY) et par les
    | anciens blocs de recommandation/assistant. Le fournisseur IA principal
    | est désormais OpenRouter (voir bloc `openrouter` ci-dessus).
    | Suppression envisagée une fois le geocoding basculé ou retiré.
    */
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        'timeout' => env('GEMINI_TIMEOUT', 10),
        'cache_ttl' => env('GEMINI_CACHE_TTL', 45), // minutes
        'max_offers' => (int) env('GEMINI_MAX_OFFERS', 40),
    ],

];
