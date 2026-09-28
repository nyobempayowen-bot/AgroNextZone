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

    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }

        if (Str::startsWith($this->avatar, ['http://', 'https://', '/'])) {
            return $this->avatar;
        }

        if (Storage::disk('public')->exists($this->avatar)) {
            return asset('storage/' . ltrim($this->avatar, '/'));
        }

        return null;
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
