<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exportador reutilizable para cualquier listado de la aplicación.
 *
 * Se construye con: el QUERY ya filtrado/ordenado tal como está en pantalla
 * (así el archivo exportado respeta los filtros activos del usuario), un
 * arreglo de encabezados legibles, y una función que convierte cada modelo
 * en una fila. Así, agregar "Exportar" a un CRUD nuevo no exige crear una
 * clase de exportación por módulo — solo estos tres datos.
 */
class GenericExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly Builder $query,
        private readonly array $headings,
        private readonly \Closure $mapRow,
    ) {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        return ($this->mapRow)($row);
    }
}
