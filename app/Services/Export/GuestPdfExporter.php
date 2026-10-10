<?php

namespace App\Services\Export;

use App\Models\Event;
use App\Services\GuestListQuery;
use App\Services\GuestMetrics;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Gera o relatório PDF premium de convidados (PRD §7), respeitando os filtros
 * atuais e incluindo indicadores consolidados, lista numerada e totalizadores.
 */
class GuestPdfExporter
{
    public function __construct(private readonly GuestListQuery $listQuery) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return string conteúdo binário do PDF
     */
    public function export(Event $event, array $filters = []): string
    {
        $guests = $this->listQuery->build($event, $filters)->with('category')->get();
        $metrics = new GuestMetrics($event);

        $pdf = Pdf::loadView('exports.guests-pdf', [
            'event' => $event,
            'guests' => $guests,
            'metrics' => [
                'confirmed_hosts' => $metrics->confirmedHosts(),
                'confirmed_companions' => $metrics->confirmedCompanions(),
                'confirmed_representatives' => $metrics->confirmedRepresentatives(),
                'not_confirmed' => $metrics->notConfirmed(),
                'present' => $metrics->present(),
                'expected_participants' => $metrics->expectedParticipants(),
                'estimated_audience' => $metrics->estimatedAudience(),
            ],
        ])->setPaper('a4', 'portrait');

        return $pdf->output();
    }
}
