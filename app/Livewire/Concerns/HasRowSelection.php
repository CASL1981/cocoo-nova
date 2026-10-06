<?php

namespace App\Livewire\Concerns;

/**
 * Selección de filas por checkbox en tablas con WithPagination.
 *
 * Mantiene un arreglo de IDs seleccionados ($selected) y la lógica de
 * "seleccionar todos" acotada a la página actual (nunca selecciona filas
 * que el usuario no ve en pantalla). Pensado para usarse junto al
 * componente Blade <x-data-table>, pero es independiente de él —
 * cualquier tabla futura puede usar este trait sin el componente, o
 * viceversa.
 */
trait HasRowSelection
{
    /** IDs (como strings, por cómo llegan los checkboxes HTML) de las filas marcadas. */
    public array $selected = [];

    /**
     * Alterna la selección de TODAS las filas de la página actual:
     * si ya están todas marcadas, las desmarca; si no, las marca todas.
     * @param array $pageIds IDs de los registros visibles en la página actual.
     */
    public function toggleSelectAll(array $pageIds): void
    {
        $pageIds = array_map('strval', $pageIds);

        $allSelected = empty(array_diff($pageIds, $this->selected));

        $this->selected = $allSelected
            ? array_values(array_diff($this->selected, $pageIds))
            : array_values(array_unique([...$this->selected, ...$pageIds]));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /** Los IDs seleccionados, convertidos a entero — útil para queries (whereIn, etc.). */
    protected function selectedIds(): array
    {
        return array_map('intval', $this->selected);
    }
}
