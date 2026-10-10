<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\Import\ImportAnalyzer;
use App\Services\Import\ImportFileReader;
use App\Services\Import\ImportParser;
use App\Services\Import\ImportPersister;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestImportController extends Controller
{
    public function __construct(
        private readonly ImportParser $parser,
        private readonly ImportFileReader $reader,
        private readonly ImportPersister $persister,
    ) {}

    /** Tela de importação (PRD §6). */
    public function create(Request $request, Event $event): Response
    {
        Gate::authorize('manageGuests', $event);

        return Inertia::render('Guests/Import', [
            'event' => ['id' => $event->id, 'name' => $event->name],
            'categories' => $event->categories()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Pré-visualização (passos 1–7). Não persiste nada (CA-08).
     */
    public function preview(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);
        $this->guardOpen($event);

        $request->validate([
            'text' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:xlsx,xls,csv,txt,md', 'max:5120'],
        ]);

        $records = $this->extractRecords($request);
        $rows = (new ImportAnalyzer($event))->analyze($records);

        $valid = array_values(array_filter($rows, fn ($r) => $r->isValid()));
        $invalid = array_values(array_filter($rows, fn ($r) => ! $r->isValid()));

        return back()->with('importPreview', [
            'rows' => array_map(fn ($r) => $r->toArray(), $rows),
            'summary' => [
                'total' => count($rows),
                'valid' => count($valid),
                'invalid' => count($invalid),
                'with_relations' => count(array_filter($rows, fn ($r) => $r->relation !== '')),
            ],
            'payload' => $records, // devolvido para confirmação posterior
        ]);
    }

    /**
     * Persistência transacional (passos 8–10) dos registros válidos.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manageGuests', $event);
        $this->guardOpen($event);

        $records = $request->validate([
            'records' => ['required', 'array'],
            'records.*' => ['array'],
        ])['records'];

        // Re-analisa no servidor: nunca confia no cliente (CA-08).
        $rows = (new ImportAnalyzer($event))->analyze($records);
        $valid = array_values(array_filter($rows, fn ($r) => $r->isValid()));

        if ($valid === []) {
            return back()->withErrors(['import' => 'Nenhum registro válido para importar.']);
        }

        $count = $this->persister->persist($event, $valid, $request->user());

        return redirect()
            ->route('guests.index', $event->id)
            ->with('success', "{$count} convidado(s) importado(s).");
    }

    /** Modelo de importação para download (PRD §6). */
    public function template(): StreamedResponse
    {
        $csv = "nome,tipo,relacao,confirmacao\n"
            ."Ana Silva,Autoridade,,0\n"
            ."Bruno Costa,Convidado geral,Ana Silva,1\n";

        return response()->streamDownload(
            fn () => print ($csv),
            'modelo-importacao.csv',
            ['Content-Type' => 'text/csv'],
        );
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function extractRecords(Request $request): array
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            return $this->reader->read($file->getRealPath(), $file->getClientOriginalExtension());
        }

        return $this->parser->parse((string) $request->input('text', ''));
    }

    private function guardOpen(Event $event): void
    {
        abort_if($event->isClosed(), 403, 'Evento encerrado: importação bloqueada.');
    }
}
