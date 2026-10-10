<?php

namespace App\Services\Export;

use App\Models\Event;
use App\Models\Guest;
use App\Services\GuestListQuery;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exporta a lista de convidados para .xlsx (PRD §7), respeitando os filtros
 * atuais (reusa GuestListQuery) e protegendo contra injeção de fórmula:
 * valores iniciados por = + - @ são escritos como texto literal (CA-11).
 */
class GuestExcelExporter
{
    public function __construct(private readonly GuestListQuery $listQuery) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function export(Event $event, array $filters = []): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Convidados');

        $headers = ['#', 'Nome', 'Categoria', 'Papel', 'Confirmação', 'Presença', 'Observação'];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue([$col + 1, 1], $label);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $rowNum = 2;
        $this->listQuery->build($event, $filters)
            ->with('category')
            ->chunk(500, function ($guests) use ($sheet, &$rowNum) {
                foreach ($guests as $guest) {
                    $this->writeRow($sheet, $rowNum++, $guest);
                }
            });

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($tmp);
        $spreadsheet->disconnectWorksheets();

        return $tmp;
    }

    private function writeRow($sheet, int $rowNum, Guest $guest): void
    {
        // Número e colunas de texto. O nome e a observação são dados do usuário:
        // escritos explicitamente como string com guarda de fórmula.
        $sheet->setCellValue([1, $rowNum], $guest->number);
        $this->setSafeString($sheet, 2, $rowNum, $guest->full_name);
        $this->setSafeString($sheet, 3, $rowNum, (string) $guest->category?->name);
        $this->setSafeString($sheet, 4, $rowNum, $guest->participation_role->label());
        $this->setSafeString($sheet, 5, $rowNum, $guest->rsvp_status->label());
        $this->setSafeString($sheet, 6, $rowNum, $guest->attendance_status->label());
        $this->setSafeString($sheet, 7, $rowNum, (string) $guest->note);
    }

    /**
     * Escreve o valor como texto, prefixando com apóstrofo quando começa com
     * caractere que o Excel interpretaria como fórmula (= + - @, tab, CR).
     */
    private function setSafeString($sheet, int $col, int $row, string $value): void
    {
        $sanitized = $this->guardFormula($value);
        $sheet->setCellValueExplicit([$col, $row], $sanitized, DataType::TYPE_STRING);
    }

    private function guardFormula(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $first = $value[0];
        if (in_array($first, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
