<?php

namespace App\Models;

use App\Enums\ContactChannel;
use App\Enums\ContactOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tentativa de contato com um convidado (M1). Append-only.
 */
class GuestContact extends Model
{
    protected $fillable = [
        'event_id',
        'guest_id',
        'channel',
        'outcome',
        'scheduled_return_at',
        'note',
        'operator_id',
    ];

    protected function casts(): array
    {
        return [
            'channel' => ContactChannel::class,
            'outcome' => ContactOutcome::class,
            'scheduled_return_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
