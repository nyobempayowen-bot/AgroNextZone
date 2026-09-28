<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * AuthController — Gestion de l'authentification.
 *
 * Regroupe l'inscription simple, la connexion, la déconnexion
 * et l'affichage des formulaires correspondants.
 */
class AuthController extends Controller
{
    /** Affiche le formulaire d'inscription. */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /** Affiche le formulaire de connexion. */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Inscription d'un nouvel utilisateur (version simple).
     * Valide les données, crée le compte avec un mot de passe chiffré,
     * connecte l'utilisateur et le redirige selon son rôle.
     */
    public function register(Request $request): RedirectResponse
    {
        // Validation des champs : nom, email unique, téléphone (chiffres
        // uniquement, 8 à 15 chiffres), rôle limité à client|producer,
        // mot de passe de 8 caractères minimum avec confirmation.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'regex:/^\+?[0-9]{8,15}$/', 'max:20'],
            'role' => ['required', 'in:client,producer'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'phone.regex' => 'Le numéro de téléphone ne doit contenir que des chiffres, avec éventuellement un + en début.',
        ]);

        // Création du compte : le mot de passe est chiffré avec Hash::make
        // et le compte démarre non vérifié (is_verified = false).
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_verified' => false,
        ]);

        // Connexion automatique après l'inscription.
        Auth::login($user);

        // Redirection selon le rôle : producteur -> son tableau de bord, client -> le sien.
        return redirect()->route($user->role === 'producer' ? 'producer.dashboard' : 'client.dashboard');
    }

    /**
     * Connexion d'un utilisateur existant.
     * Vérifie les identifiants, régénère la session (protection contre
     * la fixation de session) et redirige selon le rôle.
     */
    public function login(Request $request): RedirectResponse
    {
        // Validation minimale : email valide et mot de passe requis.
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Auth::attempt vérifie email + mot de passe contre la base.
        if (Auth::attempt($credentials)) {
            // Régénère l'identifiant de session après connexion (sécurité).
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirection selon le rôle.
            if ($user->role === 'producer') {
                return redirect()->route('producer.dashboard');
            }

            return redirect()->route('home');
        }

        // Identifiants invalides : retour au formulaire avec un message
        // d'erreur générique (sans préciser si l'email existe),
        // en conservant uniquement l'email saisi.
        return back()->withErrors([
            'email' => 'Les informations de connexion sont incorrectes.',
        ])->onlyInput('email');
    }

    /**
     * Déconnexion : détruit la session et régénère le token CSRF,
     * puis renvoie l'utilisateur vers la page d'accueil.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        // Invalide complètement la session (les données liées, comme
        // l'ancien panier en session, ne survivent pas à la déconnexion).
        $request->session()->invalidate();
        // Nouveau token CSRF pour la session suivante.
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
