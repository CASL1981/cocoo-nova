<?php

namespace App\Livewire\Concerns;

use App\Exports\GenericExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Exporta a Excel/CSV la consulta del listado con filtros y orden activos.
 */
trait ExportsRows
{
    abstract protected function rowModelClass(): string;

    /** @return Builder<covariant Model> */
    abstract protected function baseQuery(): Builder;

    abstract protected function exportFileName(): string;

    /** @return list<string> */
    abstract protected function exportHeadings(): array;

    /** @return list<mixed> */
    abstract protected function exportRow(Model $row): array;

    public function export(string $format): BinaryFileResponse
    {
        Gate::authorize('viewAny', $this->rowModelClass());

        // El formato llega desde el cliente: solo se aceptan los soportados.
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        $export = new GenericExport(
            query: $this->applySort($this->baseQuery()),
            headings: $this->exportHeadings(),
            mapRow: fn (Model $row) => $this->exportRow($row),
        );

        return Excel::download($export, $this->exportFileName().'-'.now()->format('Y-m-d_His').'.'.$format);
    }
}
