<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Filtros por columna derivados de columns(): aplica, limpia y reinicia
 * la paginación sin listar cada propiedad de filtro a mano.
 *
 * Requiere que columns() devuelva: campo => ['label', 'filter', 'type'?, 'options'?].
 */
trait HasColumnFilters
{
    /** @return array<string, array<string, mixed>> */
    abstract protected function columns(): array;

    /**
     * Hook de trait de Livewire (updated + nombre del trait): al cambiar un filtro
     * o el tamaño de página, vuelve a la primera página y limpia la selección.
     */
    public function updatedHasColumnFilters(string $property): void
    {
        if ($property === 'perPage' || str_starts_with($property, 'filter')) {
            $this->resetPage();
            $this->clearSelectionIfSelectable();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(array_values(array_filter(array_column($this->columns(), 'filter'))));
        $this->resetPage();
        $this->clearSelectionIfSelectable();
    }

    /**
     * Tamaños de página permitidos; el componente puede sobrescribirlo.
     *
     * @return list<int>
     */
    public function perPageOptions(): array
    {
        return [10, 25, 50, 100];
    }

    /** Tamaño de página validado contra las opciones permitidas. */
    protected function resolvedPerPage(): int
    {
        return in_array($this->perPage, $this->perPageOptions(), true) ? $this->perPage : 10;
    }

    /**
     * Las columnas ordenables son las mismas definidas en columns().
     *
     * @return list<string>
     */
    protected function sortableColumns(): array
    {
        return array_keys($this->columns());
    }

    /**
     * Texto: coincidencia parcial. Select: coincidencia exacta.
     */
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applyColumnFilters(Builder $query): Builder
    {
        foreach ($this->columns() as $column => $meta) {
            $value = ($meta['filter'] ?? null) ? $this->{$meta['filter']} : '';

            if ($value === '') {
                continue;
            }

            ($meta['type'] ?? 'text') === 'select'
                ? $query->where($column, $value)
                : $query->where($column, 'like', "%{$value}%");
        }

        return $query;
    }

    /** Limpia la selección de filas cuando el listado también usa HasRowSelection. */
    private function clearSelectionIfSelectable(): void
    {
        if (in_array(HasRowSelection::class, class_uses_recursive($this), true)) {
            $this->clearSelection();
        }
    }
}
