<?php

declare(strict_types=1);

namespace App\Actions\Processes;

use App\Models\ProcessSubstitution;

/**
 * Crea o actualiza un reemplazo de director. Punto único de escritura: las reglas
 * de vigencia que use la aprobación de requisiciones se apoyan en estos registros.
 */
final class SaveProcessSubstitution
{
    /** @param array<string, mixed> $data */
    public function execute(array $data, ?ProcessSubstitution $substitution = null): ProcessSubstitution
    {
        if ($substitution) {
            $substitution->update($data);

            return $substitution;
        }

        return ProcessSubstitution::create($data);
    }
}
