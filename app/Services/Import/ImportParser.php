<?php

namespace App\Services\Import;

/**
 * Faz o parsing de texto de importação em registros estruturados (PRD §6).
 *
 * Regras:
 *  - Campos separados por vírgula; registros por ';' OU quebra de linha.
 *  - Respeita campos entre aspas duplas, permitindo vírgulas e ';' internos.
 *  - Aspas duplas escapadas por duplicação ("") viram uma aspa literal.
 *
 * Formato mínimo por registro: nome,tipo,relacao,confirmacao
 * Campos adicionais são preservados posicionalmente (não obrigatórios).
 */
class ImportParser
{
    /**
     * @return array<int, array<int, string>> lista de registros, cada um com seus campos
     */
    public function parse(string $raw): array
    {
        $records = [];
        $currentFields = [];
        $buffer = '';
        $inQuotes = false;
        $length = strlen($raw);
        $hasContent = false;

        $flushField = function () use (&$currentFields, &$buffer) {
            $currentFields[] = trim($buffer);
            $buffer = '';
        };

        $flushRecord = function () use (&$records, &$currentFields, &$hasContent) {
            if ($hasContent) {
                $records[] = $currentFields;
            }
            $currentFields = [];
            $hasContent = false;
        };

        for ($i = 0; $i < $length; $i++) {
            $char = $raw[$i];

            if ($inQuotes) {
                if ($char === '"') {
                    // Aspa escapada ("").
                    if ($i + 1 < $length && $raw[$i + 1] === '"') {
                        $buffer .= '"';
                        $i++;
                    } else {
                        $inQuotes = false;
                    }
                } else {
                    $buffer .= $char;
                }

                continue;
            }

            switch ($char) {
                case '"':
                    $inQuotes = true;
                    $hasContent = true;
                    break;

                case ',':
                    $flushField();
                    $hasContent = true;
                    break;

                case ';':
                case "\n":
                    $flushField();
                    $flushRecord();
                    break;

                case "\r":
                    break; // ignora CR (CRLF)

                default:
                    $buffer .= $char;
                    if (trim($char) !== '') {
                        $hasContent = true;
                    }
            }
        }

        // Flush final.
        $flushField();
        $flushRecord();

        return $records;
    }
}
