<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'phone', 'role', 'avatar', 'bio', 'is_verified', 'password', 'adresse', 'region'])]
#[Hidden(['password', 'remember_token', 'producerVerification'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * Dossier unique contenant les photos de profil.
     * Les documents de vérification producteur (CNI, etc.) sont stockés
     * ailleurs (voir ProducerVerification) et ne doivent jamais passer ici.
     */
    public const AVATAR_DIRECTORY = 'profile_photos';

    /**
     * URL publique de la photo de profil, ou null si aucune photo valide.
     *
     * La valeur en base n'est jamais utilisée telle quelle comme URL : on
     * vérifie qu'elle pointe bien dans le dossier des photos de profil, que le
     * fichier existe réellement sur le disque `public`, et on n'autorise pas
     * les URL externes ni les traversées de chemin.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $path = $this->avatarPath();

        if ($path === null) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        // URL versionnée (mtime) pour éviter tout cache navigateur obsolète
        // après ajout / remplacement / suppression de la photo.
        $version = (int) $disk->lastModified($path);

        // L'URL doit suivre l'hôte/port/schéma réellement servis : une URL
        // absolue figée sur APP_URL (ex. http://localhost) casse dès que
        // l'application est servie ailleurs (port 8000/8123, 127.0.0.1,
        // domaine réel) et l'image se transforme en 404 -> initiales.
        // On renvoie donc un chemin relatif à la racine quand une requête
        // HTTP est disponible, et l'URL absolue du disque sinon (console).
        $url = $this->resolveAvatarPublicUrl($path, $disk);

        return $url . ($version > 0 ? '?v=' . $version : '');
    }

    /**
     * URL publique servie par le serveur web courant pour un fichier du disque.
     *
     * Renvoie un chemin relatif à la racine (`/storage/...`) si bien servi,
     * ce qui rend l'image indépendante de l'hôte, du port et du schéma.
     * Hors contexte HTTP (console, tests, jobs), on retombe sur l'URL absolue
     * configurée pour le disque.
     */
    private function resolveAvatarPublicUrl(string $path, \Illuminate\Contracts\Filesystem\Filesystem $disk): string
    {
        $prefix = (string) config('filesystems.disks.public.url');

        // On ne garde que la partie « chemin » de l'URL du disque
        // (ex. http://localhost/storage -> /storage). Le nom d'hôte est
        // volontairement écarté : c'est celui de la requête courante qui
        // doit servir l'image, pas celui figé dans APP_URL.
        if (preg_match('#^[a-z][a-z0-9+.\-]*://#i', $prefix)) {
            $urlPath = parse_url($prefix, PHP_URL_PATH);
            $prefix = is_string($urlPath) ? $urlPath : '';
        }

        $prefix = trim((string) preg_replace('#/+$#', '', $prefix), '/');

        $relative = ($prefix !== '' ? '/'.$prefix : '').'/'.$path;

        if (! $this->hasHttpContext()) {
            return $disk->url($path);
        }

        return $relative;
    }

    /**
     * Vrai lorsqu'on est dans une vraie requête HTTP (et non en console/CLI).
     */
    private function hasHttpContext(): bool
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return false;
        }

        if (! app()->bound('request')) {
            return false;
        }

        return true;
    }

    /**
     * Chemin de la photo de profil s'il est sûr et valide, sinon null.
     */
    public function avatarPath(): ?string
    {
        $value = $this->avatar;

        if (! is_string($value) || $value === '') {
            return null;
        }

        $value = str_replace('\\', '/', trim($value));

        // Pas d'URL externe, pas de chemin absolu, pas de séquence de remontée.
        if (Str::contains($value, ['://', '..']) || Str::startsWith($value, ['/', '\\'])) {
            return null;
        }

        $prefix = self::AVATAR_DIRECTORY . '/';

        if (! Str::startsWith($value, $prefix)) {
            return null;
        }

        $name = Str::after($value, $prefix);

        if ($name === '' || Str::contains($name, '/')) {
            return null;
        }

        return $prefix . $name;
    }

    /**
     * Initiales utilisées comme solution de secours (ex. "OM").
     */
    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/u', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : mb_strtoupper(mb_substr((string) $this->name, 0, 1) ?: '?');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'producer_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function producerProfile(): HasOne
    {
        return $this->hasOne(ProducerProfile::class);
    }

    public function producerVerification(): HasOne
    {
        return $this->hasOne(ProducerVerification::class, 'producer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'client_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'client_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'producer_id');
    }

    public function clientConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'client_id');
    }

    public function producerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'producer_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function producerTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'producer_id');
    }

    /** Avis reçus par ce producteur (notation directe post-transaction). */
    public function producerReviews(): HasMany
    {
        return $this->hasMany(ProducerReview::class, 'producer_id');
    }

    /** Avis producteur laissés par ce client. */
    public function givenProducerReviews(): HasMany
    {
        return $this->hasMany(ProducerReview::class, 'client_id');
    }
}
