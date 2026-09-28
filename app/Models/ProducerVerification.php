<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProducerVerification extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'cni_number',
        'document_path',
        'document_original_name',
        'status',
        'submitted_at',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $hidden = [
        'cni_number',
        'document_path',
        'document_original_name',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'cni_number' => 'encrypted',
            'document_path' => 'encrypted',
            'document_original_name' => 'encrypted',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'producer_id');
    }
}
