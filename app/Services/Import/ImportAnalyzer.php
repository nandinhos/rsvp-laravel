<?php

namespace App\Services\Import;

use App\Enums\RsvpStatus;
use App\Models\Event;

/**
 * Analisa os registros interpretados contra o evento, produzindo a
 * pré-visualização com erros e avisos (PRD §6, pipeline passos 3–7).
 *
 * NUNCA persiste nem corrige silenciosamente. Apenas classifica.
 */
class ImportAnalyzer
{
    public function __construct(private readonly Event $event) {}

    /**
     * @param  array<int, array<int, string>>  $records
     * @return ImportRow[]
     */
    public function analyze(array $records): array
    {
        $categories = $this->event->categories()
            ->get()
            ->keyBy(fn ($c) => mb_strtolower(trim($c->name)));

        // Nomes já existentes no evento (para aviso de duplicidade).
        $existingNames = $this->event->guests()
            ->pluck('full_name')
            ->map(fn ($n) => mb_strtolower(trim($n)))
            ->countBy()
            ->all();

        // Nomes presentes no próprio lote (para resolução de vínculo e duplicidade).
        $batchNames = [];
        foreach ($records as $fields) {
            $name = mb_strtolower(trim($fields[0] ?? ''));
            if ($name !== '') {
                $batchNames[$name] = ($batchNames[$name] ?? 0) + 1;
            }
        }

        $rows = [];
        foreach ($records as $index => $fields) {
            $rows[] = $this->analyzeRow(
                $index + 1,
                $fields,
                $categories,
                $existingNames,
                $batchNames,
            );
        }

        return $rows;
    }

    private function analyzeRow(
        int $line,
        array $fields,
        $categories,
        array $existingNames,
        array $batchNames,
    ): ImportRow {
        $name = trim($fields[0] ?? '');
        $categoryName = trim($fields[1] ?? '');
        $relation = trim($fields[2] ?? '');
        $rsvpFlag = $fields[3] ?? null;

        $rsvp = RsvpStatus::fromImportFlag($rsvpFlag);

        $row = new ImportRow(
            line: $line,
            name: $name,
            category: $categoryName,
            relation: $relation,
            rsvp: $rsvp->value,
        );

        // Obrigatórios.
        if ($name === '') {
            $row->addError('Nome é obrigatório.');
        }
        if ($categoryName === '') {
            $row->addError('Tipo (categoria) é obrigatório.');
        } elseif (! $categories->has(mb_strtolower($categoryName))) {
            $row->addError("Categoria \"{$categoryName}\" não existe neste evento.");
        }

        // Resolução de vínculo (relacao).
        if ($relation !== '') {
            $this->resolveRelation($row, $relation, $name, $batchNames, $existingNames);
        }

        // Duplicidade (aviso; nomes iguais são permitidos — §14.4).
        if ($name !== '') {
            $key = mb_strtolower($name);
            $inBatch = $batchNames[$key] ?? 0;
            $inEvent = $existingNames[$key] ?? 0;

            if ($inEvent > 0) {
                $row->addWarning('Já existe convidado com este nome no evento.');
            } elseif ($inBatch > 1) {
                $row->addWarning('Nome repetido dentro desta importação.');
            }
        }

        return $row;
    }

    /**
     * Resolve o vínculo por nome. Ambiguidade e ausência são ERROS (CA-08):
     * nenhum vínculo automático em nome ambíguo.
     */
    private function resolveRelation(
        ImportRow $row,
        string $relation,
        string $name,
        array $batchNames,
        array $existingNames,
    ): void {
        $key = mb_strtolower($relation);

        if ($key === mb_strtolower($name)) {
            $row->addError('O convidado não pode se relacionar a si mesmo.');

            return;
        }

        $countInBatch = $batchNames[$key] ?? 0;
        $countInEvent = $existingNames[$key] ?? 0;
        $total = $countInBatch + $countInEvent;

        if ($total === 0) {
            $row->addError("Relacionamento \"{$relation}\" não encontrado no evento nem na importação.");
        } elseif ($total > 1) {
            $row->addError("Relacionamento \"{$relation}\" é ambíguo (mais de um convidado com esse nome).");
        }
    }
}
