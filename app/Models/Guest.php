<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\ParticipationRole;
use App\Enums\RsvpStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'number',
        'full_name',
        'guest_category_id',
        'rsvp_status',
        'participation_role',
        'attendance_status',
        'checked_in_at',
        'checked_in_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'rsvp_status' => RsvpStatus::class,
            'participation_role' => ParticipationRole::class,
            'attendance_status' => AttendanceStatus::class,
            'checked_in_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GuestCategory::class, 'guest_category_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    /**
     * Vínculo em que este convidado é o dependente (acompanhante/representante).
     * Uma pessoa dependente possui no máximo um vínculo ativo com um titular.
     */
    public function hostRelationship(): HasOne
    {
        return $this->hasOne(GuestRelationship::class, 'guest_id');
    }

    /**
     * Vínculos em que este convidado é o titular representado/acompanhado.
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(GuestRelationship::class, 'related_guest_id');
    }

    /**
     * Histórico de contatos (M1), mais recente primeiro. Append-only.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(GuestContact::class)->latest();
    }

    public function isConfirmed(): bool
    {
        return $this->rsvp_status === RsvpStatus::Confirmado;
    }

    public function isPresent(): bool
    {
        return $this->attendance_status === AttendanceStatus::Presente;
    }
}
