<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

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

    /**
     * Encerra o evento (PRD §3, CA-14): bloqueia alterações operacionais.
     * Somente administrador. Auditado.
     */
    public function close(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('administer', $event);

        if (! $event->isClosed()) {
            $event->forceFill([
                'status' => EventStatus::Encerrado,
                'closed_at' => now(),
            ])->save();

            $this->audit->log('event.closed', $request->user(), $event, $event);
        }

        return back()->with('success', 'Evento encerrado.');
    }

    /**
     * Reabre o evento (PRD §3, CA-14): reabertura administrativa AUDITADA.
     * Somente administrador.
     */
    public function reopen(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('administer', $event);

        if ($event->isClosed()) {
            $event->forceFill([
                'status' => EventStatus::Ativo,
                'closed_at' => null,
            ])->save();

            $this->audit->log('event.reopened', $request->user(), $event, $event);
        }

        return back()->with('success', 'Evento reaberto.');
    }

    /**
     * Página de auditoria (PRD §9, CA-10). Somente administrador do evento.
     */
    public function audit(Request $request, Event $event): Response
    {
        Gate::authorize('administer', $event);

        $logs = $event->auditLogs()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->through(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->user?->name ?? 'Sistema',
                'created_at' => $log->created_at?->timezone($event->timezone)->format('d/m/Y H:i:s'),
                'ip_address' => $log->ip_address,
                'changes' => $log->changes,
            ]);

        return Inertia::render('Events/Audit', [
            'event' => ['id' => $event->id, 'name' => $event->name],
            'logs' => $logs,
        ]);
    }
}
