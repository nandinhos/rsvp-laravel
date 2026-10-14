<?php

namespace App\Http\Controllers;

use App\Enums\ContactChannel;
use App\Enums\ContactOutcome;
use App\Models\Event;
use App\Models\Guest;
use App\Services\AuditLogger;
use App\Services\ContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Registro de contatos de confirmação ativa (M1). Append-only, isolado por
 * evento (CA-01) e bloqueado em evento encerrado (CA-14).
 */
class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $contacts,
        private readonly AuditLogger $audit,
    ) {}

    public function store(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('operate', $event);
        abort_unless($guest->event_id === $event->id, 404);
        abort_if($event->isClosed(), 403, 'Evento encerrado: alterações bloqueadas.');

        $data = $request->validate([
            'channel' => ['required', Rule::enum(ContactChannel::class)],
            'outcome' => ['required', Rule::enum(ContactOutcome::class)],
            'scheduled_return_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $contact = $this->contacts->record($guest, $request->user(), [
            'channel' => ContactChannel::from($data['channel']),
            'outcome' => ContactOutcome::from($data['outcome']),
            'scheduled_return_at' => $data['scheduled_return_at'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        $this->audit->log('guest.contact_recorded', $request->user(), $event, $guest, [
            'channel' => $contact->channel->value,
            'outcome' => $contact->outcome->value,
        ]);

        return back()->with('success', 'Contato registrado.');
    }
}
