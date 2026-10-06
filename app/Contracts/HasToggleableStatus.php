<?php

namespace App\Contracts;

/**
 * Implementado por cualquier modelo cuyo estado se pueda alternar en lote
 * desde un listado (botón "Activar/Desactivar"). HasBulkRowActions llama
 * a toggleStatus() sin necesitar conocer el Enum específico de cada
 * modelo — Product usa ProductStatus, otro módulo usará el suyo propio.
 */
interface HasToggleableStatus
{
    public function toggleStatus(): void;
}
