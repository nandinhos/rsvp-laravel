<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\ParticipationRole;
use App\Enums\RsvpStatus;
use App\Exceptions\GuestDomainException;
use App\Models\Event;
use App\Models\Guest;
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
            $this->writer->create($event, $data);
        } catch (GuestDomainException $e) {
            return back()->withErrors(['guest_category_id' => $e->getMessage()])->withInput();
        }

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

        return back()->with('success', 'Convidado removido.');
    }

    public function confirm(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('operate', $event);
        $this->guardOwnership($event, $guest);
        $this->guardOpen($event);

        $this->rsvp->confirm($guest);

        return back()->with('success', 'Participação confirmada.');
    }

    public function decline(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('operate', $event);
        $this->guardOwnership($event, $guest);
        $this->guardOpen($event);

        $this->rsvp->decline($guest);

        return back()->with('success', 'Ausência registrada.');
    }

    public function checkIn(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('operate', $event);
        $this->guardOwnership($event, $guest);
        $this->guardOpen($event);

        $this->checkIn->checkIn($guest, $request->user());

        return back()->with('success', 'Credenciamento registrado.');
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
