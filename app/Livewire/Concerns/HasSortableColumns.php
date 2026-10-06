<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Comportamiento de ordenamiento de tablas (alternar columna/dirección
 * y aplicar el orden al query).
 *
 * IMPORTANTE: este trait NO declara $sortBy ni $sortDirection.
 * Cada componente que lo use debe declarar esas dos propiedades él mismo
 * (normalmente con su propio #[Session(key: '...')], ya que la clave y el
 * valor por defecto varían según la pantalla — por ejemplo, Role y User
 * ordenan por 'id' mientras que ServicePoint ordena por 'code').
 * Declarar las propiedades aquí causaba un conflicto fatal de PHP en
 * cuanto una pantalla nueva usara un valor por defecto distinto al de
 * otra, porque PHP exige que una clase y un trait que comparten el
 * nombre de una propiedad tengan una declaración idéntica.
 */
trait HasSortableColumns
{
    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortableColumns(), true)) {
            return;
        }

        $this->sortDirection = ($this->sortBy === $column && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortBy = $column;
        $this->resetPage();
    }

    protected function applySort(Builder $query): Builder
    {
        $columns = $this->sortableColumns();
        $column = in_array($this->sortBy, $columns, true) ? $this->sortBy : ($columns[0] ?? 'id');
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($column, $direction);
    }

    // Cada componente define cuáles de sus columnas son ordenables.
    abstract protected function sortableColumns(): array;
}
