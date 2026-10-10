<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Interpreta arquivos de importação conforme o formato real (PRD §6 passo 2):
 * xlsx (planilha), csv/txt (texto delimitado) e md (tabela Markdown).
 *
 * Produz registros estruturados (mesma forma do ImportParser) para que o
 * restante do pipeline seja agnóstico ao formato de origem.
 */
class ImportFileReader
{
    public function __construct(private readonly ImportParser $parser) {}

    /**
     * @return array<int, array<int, string>>
     */
    public function read(string $path, string $extension): array
    {
        return match (strtolower($extension)) {
            'xlsx', 'xls' => $this->readSpreadsheet($path),
            'md' => $this->readMarkdown((string) file_get_contents($path)),
            default => $this->parser->parse((string) file_get_contents($path)), // csv, txt
        };
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $records = [];

        foreach ($sheet->toArray(null, true, true, false) as $row) {
            $fields = array_map(fn ($v) => trim((string) ($v ?? '')), $row);

            // Ignora linhas totalmente vazias.
            if (implode('', $fields) === '') {
                continue;
            }

            $records[] = $fields;
        }

        return $this->dropHeaderIfPresent($records);
    }

    /**
     * Interpreta uma tabela Markdown (linhas com '|'), descartando a linha
     * separadora (|---|---|).
     *
     * @return array<int, array<int, string>>
     */
    private function readMarkdown(string $content): array
    {
        $records = [];

        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            $line = trim($line);

            if ($line === '' || ! str_contains($line, '|')) {
                continue;
            }

            // Linha separadora de cabeçalho: só traços, dois-pontos, pipes e espaços.
            if (preg_match('/^\|?[\s:|-]+\|?$/', $line) && str_contains($line, '-')) {
                continue;
            }

            $cells = array_map('trim', explode('|', trim($line, '|')));
            $records[] = $cells;
        }

        return $this->dropHeaderIfPresent($records);
    }

    /**
     * Remove a primeira linha se ela parecer um cabeçalho (contém "nome").
     *
     * @param  array<int, array<int, string>>  $records
     * @return array<int, array<int, string>>
     */
    private function dropHeaderIfPresent(array $records): array
    {
        if ($records === []) {
            return $records;
        }

        $first = mb_strtolower(implode(',', $records[0]));

        if (str_contains($first, 'nome')) {
            array_shift($records);
        }

        return $records;
    }
}
