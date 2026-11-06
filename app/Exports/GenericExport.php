<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
/**
 * @implements WithMapping<Model>
 */
class GenericExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Builder<covariant Model>  $query
     * @param  list<string>  $headings
     */
    public function __construct(
        private readonly Builder $query,
        private readonly array $headings,
        private readonly \Closure $mapRow,
    ) {}

    /** @return Builder<covariant Model> */
    public function query(): Builder
    {
        return $this->query;
    }

    /** @return list<string> */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * @param  Model  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return ($this->mapRow)($row);
    }
}
