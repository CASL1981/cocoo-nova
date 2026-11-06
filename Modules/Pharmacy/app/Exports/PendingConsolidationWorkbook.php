<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Libro del consolidado: hoja INFORME (pendientes) y hoja PRODUCTOS NUEVOS,
 * ambas con el formato de PendingConsolidationExport.
 */
final class PendingConsolidationWorkbook implements Export, WithMultipleSheets
{
    /**
     * @param  array{columns: array<int|string, string>, rows: list<array{product: string, quantities: array<int|string, int>, total: int}>}  $pendings
     * @param  array{columns: array<int|string, string>, rows: list<array{product: string, quantities: array<int|string, int>, total: int}>}  $newProducts
     */
    public function __construct(private readonly array $pendings, private readonly array $newProducts) {}

    /** @return list<PendingConsolidationExport> */
    public function sheets(): array
    {
        return [
            new PendingConsolidationExport($this->pendings),
            new PendingConsolidationExport($this->newProducts, 'PRODUCTOS NUEVOS'),
        ];
    }
}
