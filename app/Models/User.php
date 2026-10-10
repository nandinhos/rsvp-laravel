<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\EventRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
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
        ];
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->using(EventUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Papel do usuário em um evento específico, ou null se não autorizado (CA-01).
     */
    public function roleFor(Event $event): ?EventRole
    {
        $pivot = $this->events()
            ->where('events.id', $event->id)
            ->first()?->pivot;

        return $pivot?->role;
    }

    /** O usuário possui qualquer vínculo com o evento. */
    public function belongsToEvent(Event $event): bool
    {
        return $this->roleFor($event) !== null;
    }
}
