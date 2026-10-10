<?php

namespace App\Services\Import;

/**
 * Representa uma linha interpretada da importação, com os campos normalizados
 * e as mensagens de erro/aviso detectadas na pré-visualização (PRD §6, CA-08).
 */
class ImportRow
{
    /** @var string[] */
    public array $errors = [];

    /** @var string[] */
    public array $warnings = [];

    public function __construct(
        public int $line,
        public string $name = '',
        public string $category = '',
        public string $relation = '',
        public ?string $rsvp = null,
    ) {}

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function toArray(): array
    {
        return [
            'line' => $this->line,
            'name' => $this->name,
            'category' => $this->category,
            'relation' => $this->relation,
            'rsvp' => $this->rsvp,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'valid' => $this->isValid(),
        ];
    }
}
