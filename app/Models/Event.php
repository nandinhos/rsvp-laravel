<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    /** Categorias padrão iniciais (PRD §2.2), configuráveis por evento. */
    public const DEFAULT_CATEGORIES = [
        'Autoridade',
        'Diretoria',
        'Comitê',
        'Convidado geral',
    ];

    protected $fillable = [
        'name',
        'starts_at',
        'location',
        'cover_image_path',
        'visibility',
        'estimated_audience',
        'status',
        'timezone',
        'retention_days',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'closed_at' => 'datetime',
            'visibility' => EventVisibility::class,
            'status' => EventStatus::class,
            'estimated_audience' => 'integer',
            'guest_number_seq' => 'integer',
            'retention_days' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(EventUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function eventUsers(): HasMany
    {
        return $this->hasMany(EventUser::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(GuestCategory::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(GuestRelationship::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isClosed(): bool
    {
        return $this->status === EventStatus::Encerrado;
    }
}
