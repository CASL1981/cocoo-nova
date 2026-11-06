<?php

declare(strict_types=1);

namespace App\Actions\Processes;

use App\Models\Process;

/**
 * Crea o actualiza un proceso. Punto único de escritura del catálogo.
 */
final class SaveProcess
{
    /** @param array<string, mixed> $data */
    public function execute(array $data, ?Process $process = null): Process
    {
        if ($process) {
            $process->update($data);

            return $process;
        }

        return Process::create($data);
    }
}
