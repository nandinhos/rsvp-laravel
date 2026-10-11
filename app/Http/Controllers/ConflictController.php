<?php

namespace App\Http\Controllers;

use App\Enums\ParticipationRole;
use App\Enums\RelationshipType;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\GuestRelationship;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Resolução explícita de conflitos de representação (PRD §2.5, CA-06):
 * quando titular e representante estão ambos confirmados, um operador
 * autorizado decide quem participa. Nada é alterado silenciosamente.
 */
class ConflictController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request, Event $event): Response
    {
        Gate::authorize('operate', $event);

        $conflicts = $event->relationships()
            ->where('is_conflict', true)
            ->whereNull('resolved_at')
            ->with(['guest:id,full_name,number', 'relatedGuest:id,full_name,number'])
            ->get()
            ->map(fn (GuestRelationship $r) => [
                'id' => $r->id,
                'representative' => [
                    'id' => $r->guest->id,
                    'name' => $r->guest->full_name,
                    'number' => $r->guest->number,
                ],
                'host' => [
                    'id' => $r->relatedGuest->id,
                    'name' => $r->relatedGuest->full_name,
                    'number' => $r->relatedGuest->number,
                ],
            ]);

        return Inertia::render('Events/Conflicts', [
            'event' => ['id' => $event->id, 'name' => $event->name],
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Resolve o conflito escolhendo quem participa:
     *  - 'host': mantém o titular; representante não participará;
     *  - 'representative': mantém o representante; titular não participará;
     *  - 'both': mantém ambos (caso de erro de cadastro) e rebaixa o
     *    representante de volta a acompanhante — os dois comparecem.
     */
    public function resolve(Request $request, Event $event, GuestRelationship $relationship): RedirectResponse
    {
        Gate::authorize('operate', $event);
        abort_unless($relationship->event_id === $event->id, 404);
        $this->guardOpen($event);

        $data = $request->validate([
            'keep' => ['required', 'in:host,representative,both'],
        ]);

        DB::transaction(function () use ($relationship, $data, $request, $event) {
            $relationship->loadMissing(['guest', 'relatedGuest']);
            $representative = $relationship->guest;
            $host = $relationship->relatedGuest;

            if ($data['keep'] === 'host') {
                // Mantém o titular; representante não participará.
                $representative->forceFill(['rsvp_status' => RsvpStatus::NaoParticipara])->save();
            } elseif ($data['keep'] === 'representative') {
                // Mantém o representante; titular não participará.
                $host->forceFill(['rsvp_status' => RsvpStatus::NaoParticipara])->save();
            } else {
                // Mantém ambos: rebaixa o representante a acompanhante
                // (ambos confirmados comparecem; corrige provável erro de cadastro).
                $relationship->forceFill(['type' => RelationshipType::Acompanhante])->save();
                $representative->forceFill(['participation_role' => ParticipationRole::Acompanhante])->save();
            }

            $relationship->forceFill([
                'is_conflict' => false,
                'resolved_at' => now(),
                'resolved_by' => $request->user()->id,
            ])->save();

            $this->audit->log(
                action: 'conflict.resolved',
                user: $request->user(),
                event: $event,
                auditable: $relationship,
                changes: ['keep' => $data['keep']],
            );
        });

        return back()->with('success', 'Conflito resolvido.');
    }

    private function guardOpen(Event $event): void
    {
        abort_if($event->isClosed(), 403, 'Evento encerrado: alterações bloqueadas.');
    }
}
