<?php

namespace App\Models;

use App\Enums\RelationshipType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'guest_id',
        'related_guest_id',
        'type',
        'origin_type',
        'is_conflict',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => RelationshipType::class,
            'origin_type' => RelationshipType::class,
            'is_conflict' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** O convidado dependente (acompanhante/representante). */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'guest_id');
    }

    /** O convidado titular representado/acompanhado. */
    public function relatedGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'related_guest_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
