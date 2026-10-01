<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'read_at',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at'   => 'datetime',
            'edited_at' => 'datetime',
        ];
    }

    /** Vrai si le message a déjà été modifié au moins une fois. */
    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
