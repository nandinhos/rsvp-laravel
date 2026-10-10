<?php

namespace App\Models;

use App\Enums\EventRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EventUser extends Pivot
{
    protected $table = 'event_user';

    public $incrementing = true;

    protected $fillable = [
        'event_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => EventRole::class,
        ];
    }
}
