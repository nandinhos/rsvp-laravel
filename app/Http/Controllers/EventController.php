<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * Seleção de eventos: lista apenas os eventos autorizados ao usuário
     * (PRD §3, CA-01). Nunca expõe eventos aos quais não pertence.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $events = $user->events()
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'name' => $event->name,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'location' => $event->location,
                'status' => $event->status->value,
                'status_label' => $event->status->label(),
                'role' => $event->pivot->role->value,
                'role_label' => $event->pivot->role->label(),
            ]);

        return Inertia::render('Events/Index', [
            'events' => $events,
        ]);
    }
}
