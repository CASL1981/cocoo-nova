<?php

namespace App\Support;

/**
 * Límites de las acciones masivas sobre listados (trait HasRowSelection).
 */
final class BulkSelection
{
    /** Máximo de registros que procesa una acción masiva con "seleccionar todos los que coinciden". */
    public const MAX_MATCHING = 1000;
}
