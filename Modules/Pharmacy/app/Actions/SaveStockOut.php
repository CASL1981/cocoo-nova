<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Actions;

use Modules\Pharmacy\App\Models\StockOut;

/**
 * Crea o actualiza un agotado.
 *
 * Punto único de escritura: en la fase 5 aquí se reevaluarán las líneas
 * de solicitud afectadas por el agotado.
 */
final class SaveStockOut
{
    public function execute(array $data, ?StockOut $stockOut = null): StockOut
    {
        if ($stockOut) {
            $stockOut->update($data);

            return $stockOut;
        }

        return StockOut::create($data);
    }
}
