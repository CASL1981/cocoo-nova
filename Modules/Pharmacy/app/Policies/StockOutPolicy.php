<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Policies;

use App\Policies\Concerns\HasStandardModuleAbilities;

/**
 * Autorización del catálogo de agotados (gestionado por Compras).
 */
final class StockOutPolicy
{
    use HasStandardModuleAbilities;

    protected string $module = 'stock-outs';
}
