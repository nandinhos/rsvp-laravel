<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\Export\GuestExcelExporter;
use App\Services\Export\GuestPdfExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exportações de convidados (PRD §7). Permissão verificada no servidor (CA-11):
 * apenas administrador e gestor podem exportar. Respeita os filtros recebidos.
 */
class GuestExportController extends Controller
{
    public function __construct(
        private readonly GuestExcelExporter $excel,
        private readonly GuestPdfExporter $pdf,
    ) {}

    public function excel(Request $request, Event $event): BinaryFileResponse
    {
        Gate::authorize('export', $event);

        $filters = $request->only(['search', 'category', 'role', 'rsvp', 'attendance']);
        $path = $this->excel->export($event, $filters);

        $filename = $this->filename($event, 'xlsx');

        return response()
            ->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function pdf(Request $request, Event $event): Response
    {
        Gate::authorize('export', $event);

        $filters = $request->only(['search', 'category', 'role', 'rsvp', 'attendance']);
        $content = $this->pdf->export($event, $filters);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($event, 'pdf').'"',
        ]);
    }

    private function filename(Event $event, string $ext): string
    {
        $slug = Str::slug($event->name) ?: 'evento';

        return "convidados-{$slug}.{$ext}";
    }
}
