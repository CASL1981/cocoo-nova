<?php

namespace App\Actions\Imports;

/**
 * Resultado de una importación: si hay errores no se guardó ningún registro.
 */
final readonly class ImportResult
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public array $errors = [],
    ) {}

    public function failed(): bool
    {
        return $this->errors !== [];
    }
}
