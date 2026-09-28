<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProducerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_type',
        'specialty',
        'main_products',
        'farm_name',
        'years_experience',
        'description',
    ];

    protected function casts(): array
    {
        return ['years_experience' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
