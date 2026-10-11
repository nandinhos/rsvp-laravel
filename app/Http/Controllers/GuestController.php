<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\ParticipationRole;
use App\Enums\RsvpStatus;
use App\Exceptions\GuestDomainException;
use App\Models\Event;
use App\Models\Guest;
use App\Services\AuditLogger;
use App\Services\CheckInService;
use App\Services\GuestListQuery;
use App\Services\GuestWriter;
use App\Services\RsvpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GuestController extends Controller
{
    public function __construct(
        private readonly GuestListQuery $listQuery,
        private readonly GuestWriter $writer,
        private readonly RsvpService $rsvp,
        private readonly CheckInService $checkIn,
        private readonly AuditLogger $audit,
    ) {}

    /** Lista numerada com busca, filtros e ordenação (PRD §5, CA-01). */
    public function index(Request $request, Event $event): Response
    {
        Gate::authorize('view', $event);

        $filters = $request->only(['search', 'category', 'role', 'rsvp', 'attendance']);

        $guests = $this->listQuery->build($event, $filters)
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Guest $g) => [
                'id' => $g->id,
                'number' => $g->number,
                'full_name' => $g->full_name,
                'category' => $g->category?->name,
                'rsvp_status' => $g->rsvp_status->value,
                'rsvp_label' => $g->rsvp_status->label(),
                'participation_role' => $g->participation_role->value,
                'role_label' => $g->participation_role->label(),
                'attendance_status' => $g->attendance_status->value,
                'attendance_label' => $g->attendance_status->label(),
            ]);

        return Inertia::render('Guests/Index', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'status' => $event->status->value,
            ],
            'guests' => $guests,
            'filters' => $filters,
            'categories' => $event->categories()->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name']),
            'options' => $this->filterOptions(),
            'can' => [
                'manageGuests' => $request->user()->can('manageGuests', $event),
                'operate' => $request->user()->can('operate', $event),
                'export' => $request->user()->can('export', $event),
            ],
        ]);
    }

    /**
     * Estação de credenciamento (portaria): busca unificada, contadores ao
     * vivo e credenciamento rápido (CA-09, CA-12). Ordenação estável pela
     * numeração do evento — credenciar NÃO reordena a lista.
     *
     * A lista é dividida por aptidão ao credenciamento:
     *  - "eligible": convidados confirmados (podem ser credenciados);
     *  - "pending":  convidados com RSVP pendente (precisam confirmar antes).
     * Quem recusou (não participará) não aparece na estação.
     */
    public function checkInStation(Request $request, Event $event): Response
    {
        Gate::authorize('view', $event);

        $search = (string) $request->query('search', '');

        $toArray = fn (Guest $g) => [
            'id' => $g->id,
            'number' => $g->number,
            'full_name' => $g->full_name,
            'category' => $g->category?->name,
            'rsvp_status' => $g->rsvp_status->value,
            'rsvp_label' => $g->rsvp_status->label(),
            'role_label' => $g->participation_role->label(),
            'attendance_status' => $g->attendance_status->value,
            'attendance_label' => $g->attendance_status->label(),
        ];

        $rows = $this->listQuery->build($event, ['search' => $search])->limit(200)->get();

        $eligible = $rows
            ->filter(fn (Guest $g) => $g->rsvp_status === RsvpStatus::Confirmado)
            ->map($toArray)->values();

        $pending = $rows
            ->filter(fn (Guest $g) => $g->rsvp_status === RsvpStatus::Pendente)
            ->map($toArray)->values();

        $all = $event->guests()->get(['rsvp_status', 'attendance_status']);

        return Inertia::render('Guests/CheckIn', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'status' => $event->status->value,
            ],
            'eligible' => $eligible,
            'pending' => $pending,
            'search' => $search,
            'counters' => [
                'present' => $all->where('attendance_status', AttendanceStatus::Presente->value)->count(),
                'confirmed' => $all->where('rsvp_status', RsvpStatus::Confirmado->value)->count(),
                'awaiting' => $all->where('attendance_status', AttendanceStatus::Aguardando->value)
                    ->where('rsvp_status', RsvpStatus::Confirmado->value)->count(),
                'pending' => $all->where('rsvp_status', RsvpStatus::Pendente->value)->count(),
                'total' => $all->count(),
            ],
            'can' => [
                'operate' => $request->user()->can('operate', $event),
            ],
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);
        $this->guardOpen($event);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'guest_category_id' => ['required', 'integer'],
            'rsvp_status' => ['nullable', 'string'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $created = $this->writer->create($event, $data);
        } catch (GuestDomainException $e) {
            return back()->withErrors(['guest_category_id' => $e->getMessage()])->withInput();
        }

        $this->audit->log('guest.created', $request->user(), $event, $created, ['name' => $created->full_name]);

        return back()->with('success', 'Convidado adicionado.');
    }

    public function update(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);
        $this->guardOwnership($event, $guest);
        $this->guardOpen($event);

        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'guest_category_id' => ['sometimes', 'integer'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->writer->update($guest, $data);
        } catch (GuestDomainException $e) {
            return back()->withErrors(['guest_category_id' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Convidado atualizado.');
    }

    public function destroy(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);
        $this->guardOwnership($event, $guest);
        $this->guardOpen($event);

        $guest->delete(); // exclusão lógica (PRD §5)

        $this->audit->log('guest.deleted', $request->user(), $event, $guest, ['name' => $guest->full_name]);

        return back()->with('success', 'Convidado removido.');
    }

    public function confirm(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('operate', $event);
        $this->guardOwnership($event, $guest);
        $this->guardOpen($event);

        $this->rsvp->confirm($guest);

        $this->audit->log('guest.confirmed', $request->user(), $event, $guest);

        return back()->with('success', 'Participação confirmada.');
    }

    public function decline(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('operate', $event);
        $this->guardOwnership($event, $guest);
        $this->guardOpen($event);

        $this->rsvp->decline($guest);

        $this->audit->log('guest.declined', $request->user(), $event, $guest);

        return back()->with('success', 'Ausência registrada.');
    }

    /**
     * Reconsideração (mudança de escolha): um convidado que havia recusado
     * ("não participará") volta a Pendente, de forma explícita e auditada —
     * jamais reabre uma recusa silenciosamente.
     */
    public function reconsider(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('operate', $event);
        $this->guardOwnership($event, $guest);
        $this->guardOpen($event);

        abort_unless($guest->rsvp_status === RsvpStatus::NaoParticipara, 422);

        $this->rsvp->reset($guest); // volta a Pendente

        $this->audit->log('guest.rsvp_reconsidered', $request->user(), $event, $guest, [
            'from' => RsvpStatus::NaoParticipara->value,
            'to' => RsvpStatus::Pendente->value,
        ]);

        return back()->with('success', 'Recusa reconsiderada: convidado retornou para pendente.');
    }

    public function checkIn(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('operate', $event);
        $this->guardOwnership($event, $guest);
        $this->guardOpen($event);

        try {
            $this->checkIn->checkIn($guest, $request->user());
        } catch (GuestDomainException $e) {
            return back()->withErrors(['check_in' => $e->getMessage()]);
        }

        $this->audit->log('guest.checked_in', $request->user(), $event, $guest);

        return back()->with('success', 'Credenciamento registrado.');
    }

    /** Reverte o credenciamento (desfazer imediato, PRD §2.6). */
    public function revertCheckIn(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('operate', $event);
        $this->guardOwnership($event, $guest);
        $this->guardOpen($event);

        $this->checkIn->undo($guest);

        $this->audit->log('guest.check_in_reverted', $request->user(), $event, $guest);

        return back()->with('success', 'Credenciamento desfeito.');
    }

    /** Retorna o RSVP para pendente (desfazer confirmação/recusa). */
    public function undoRsvp(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('operate', $event);
        $this->guardOwnership($event, $guest);
        $this->guardOpen($event);

        $this->rsvp->reset($guest);

        $this->audit->log('guest.rsvp_reset', $request->user(), $event, $guest);

        return back()->with('success', 'Confirmação desfeita.');
    }

    /**
     * Garante que o convidado pertence ao evento da rota (CA-01):
     * impede acesso cruzado via manipulação de IDs na URL.
     */
    private function guardOwnership(Event $event, Guest $guest): void
    {
        abort_unless($guest->event_id === $event->id, 404);
    }

    /** Bloqueia alterações operacionais em evento encerrado (CA-14). */
    private function guardOpen(Event $event): void
    {
        abort_if($event->isClosed(), 403, 'Evento encerrado: alterações bloqueadas.');
    }

    private function filterOptions(): array
    {
        return [
            'rsvp' => collect(RsvpStatus::cases())
                ->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'role' => collect(ParticipationRole::cases())
                ->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'attendance' => collect(AttendanceStatus::cases())
                ->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
        ];
    }
}
