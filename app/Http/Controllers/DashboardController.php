<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\GuestMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Painel de indicadores do evento (PRD §4), consumindo GuestMetrics.
     * Autorização por evento (CA-01). Métricas sem dupla contagem (CA-07) e
     * com proteção contra divisão por zero (taxas null => indisponível).
     */
    public function show(Request $request, Event $event): Response
    {
        Gate::authorize('view', $event);

        $metrics = new GuestMetrics($event);

        return Inertia::render('Events/Dashboard', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'location' => $event->location,
                'status' => $event->status->value,
                'status_label' => $event->status->label(),
                'estimated_audience' => $event->estimated_audience,
            ],
            'metrics' => [
                'confirmed_hosts' => $metrics->confirmedHosts(),
                'confirmed_companions' => $metrics->confirmedCompanions(),
                'confirmed_representatives' => $metrics->confirmedRepresentatives(),
                'not_confirmed' => $metrics->notConfirmed(),
                'present' => $metrics->present(),
                'expected_participants' => $metrics->expectedParticipants(),
                'estimated_audience' => $metrics->estimatedAudience(),
                'confirmation_rate' => $metrics->confirmationRate(),
                'attendance_rate' => $metrics->attendanceRate(),
                'has_pending_conflicts' => $metrics->hasPendingConflicts(),
            ],
            'can' => [
                'operate' => $request->user()->can('operate', $event),
                'manageGuests' => $request->user()->can('manageGuests', $event),
            ],
        ]);
    }
}
