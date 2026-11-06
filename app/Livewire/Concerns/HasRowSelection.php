<?php

namespace App\Livewire\Concerns;

use App\Support\BulkSelection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

/**
 * Selección de filas por checkbox en tablas con WithPagination.
 *
 * Mantiene un arreglo de IDs seleccionados ($selected) y la lógica de
 * "seleccionar todos" acotada a la página actual. Cuando la página completa
 * está marcada, el usuario puede ampliar la selección a todos los registros
 * que coinciden con los filtros ($selectAllMatching, patrón tipo Gmail): los
 * IDs los resuelve el servidor con la consulta filtrada del listado, nunca el
 * navegador. Pensado para usarse junto al componente <x-table.selection-banner>.
 *
 * El componente debe tener baseQuery() (la misma consulta de la tabla) o
 * sobrescribir selectionQuery().
 */
trait HasRowSelection
{
    /**
     * IDs (como strings, por cómo llegan los checkboxes HTML) de las filas marcadas.
     *
     * @var list<string>
     */
    public array $selected = [];

    /** La selección abarca todos los registros que coinciden con los filtros; solo la activa el servidor. */
    #[Locked]
    public bool $selectAllMatching = false;

    /**
     * Alterna la selección de TODAS las filas de la página actual:
     * si ya están todas marcadas, las desmarca; si no, las marca todas.
     *
     * @param  array<int, int|string>  $pageIds  IDs de los registros visibles en la página actual.
     */
    public function toggleSelectAll(array $pageIds): void
    {
        $pageIds = array_map('strval', $pageIds);

        $allSelected = empty(array_diff($pageIds, $this->selected));

        $this->selectAllMatching = false;
        $this->selected = $allSelected
            ? array_values(array_diff($this->selected, $pageIds))
            : array_values(array_unique([...$this->selected, ...$pageIds]));
    }

    /** Amplía la selección a todos los registros que coinciden con los filtros (hasta el máximo). */
    public function selectAllMatchingRows(): void
    {
        $count = $this->selectionQuery()->count();

        if ($count > BulkSelection::MAX_MATCHING) {
            // El aviso solo se muestra si el componente usa Notifies (no todos los listados lo usan).
            if (in_array(Notifies::class, class_uses_recursive($this), true)) {
                $this->toastError('Los filtros traen '.number_format($count).' registros: el máximo por acción es '
                    .number_format(BulkSelection::MAX_MATCHING).'. Afine los filtros.');
            }

            return;
        }

        $this->selectAllMatching = true;
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAllMatching = false;
    }

    /**
     * Hook de trait de Livewire: marcar o desmarcar una fila, o cambiar un filtro,
     * cancela la selección ampliada (para no procesar registros distintos a los contados).
     */
    public function updatedHasRowSelection(string $property): void
    {
        if (! $this->selectAllMatching) {
            return;
        }

        $root = explode('.', $property)[0];

        if ($root === 'selected') {
            $this->selectAllMatching = false;
        } elseif (str_starts_with($root, 'filter') || in_array($root, ['search', 'perPage', 'onlyWithStock'], true)) {
            $this->clearSelection();
        }
    }

    /** Cantidad real de registros sobre los que actuarán las acciones masivas. */
    public function selectedCount(): int
    {
        return $this->selectAllMatching
            ? min($this->selectionQuery()->count(), BulkSelection::MAX_MATCHING)
            : count($this->selected);
    }

    /**
     * Total de registros que coinciden con los filtros, solo cuando el aviso de selección
     * debe mostrarse (página completa marcada o selección ampliada); null en otro caso.
     *
     * @param  array<int, int|string>  $pageIds
     */
    public function matchingSelectionCount(array $pageIds): ?int
    {
        $pageIds = array_map('strval', $pageIds);
        $pageSelected = $pageIds !== [] && empty(array_diff($pageIds, $this->selected));

        return ($this->selectAllMatching || $pageSelected) ? $this->selectionQuery()->count() : null;
    }

    /**
     * Abre el modal de edición del único registro seleccionado y limpia la selección.
     *
     * Así, al guardar o cancelar no queda la fila marcada y el usuario puede marcar
     * otra y volver a editar sin desmarcar la anterior. El modal sigue autorizando
     * el registro recibido; aquí solo se acepta un evento "open-*-edit".
     */
    public function editSelected(string $event, string $param): void
    {
        if ($this->selectAllMatching || count($this->selected) !== 1
            || preg_match('/^open-[a-z-]+-edit$/', $event) !== 1 || preg_match('/^[a-zA-Z]+$/', $param) !== 1) {
            return;
        }

        $id = $this->selectedIds()[0];
        $this->clearSelection();
        $this->dispatch($event, ...[$param => $id]);
    }

    /**
     * Consulta de la que salen los registros de "seleccionar todos los que coinciden":
     * por defecto la misma del listado (filtros activos incluidos).
     *
     * @return Builder<covariant Model>
     */
    protected function selectionQuery(): Builder
    {
        return $this->baseQuery();
    }

    /**
     * Los IDs seleccionados como enteros, útiles para queries (whereIn, etc.).
     * Con la selección ampliada se obtienen de la consulta filtrada (hasta el máximo).
     *
     * @return list<int>
     */
    protected function selectedIds(): array
    {
        if ($this->selectAllMatching) {
            $query = $this->selectionQuery();

            return array_values($query->toBase()->reorder()->limit(BulkSelection::MAX_MATCHING)
                ->pluck($query->getModel()->getQualifiedKeyName())
                ->map(fn (mixed $id): int => (int) $id)->all());
        }

        return array_map('intval', $this->selected);
    }
}
