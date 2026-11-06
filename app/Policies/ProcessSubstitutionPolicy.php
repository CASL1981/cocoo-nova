<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\HasStandardModuleAbilities;

/**
 * Autorización de los reemplazos de directores de proceso (los registra el Administrador).
 */
final class ProcessSubstitutionPolicy
{
    use HasStandardModuleAbilities;

    protected string $module = 'process-substitutions';
}
