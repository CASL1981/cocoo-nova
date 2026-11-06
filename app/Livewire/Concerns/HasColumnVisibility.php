<?php

namespace App\Livewire\Concerns;

/**
 * Configuración de columnas visibles de un listado (botón de columnas en <x-data-table>).
 *
 * Las columnas salen de columns(); una columna con 'hideable' => false no se puede ocultar.
 * La preferencia se guarda por usuario: el componente declara la propiedad con su
 * propia clave de sesión (un trait no declara propiedades que varían por consumidor):
 *
 *     #[Session(key: 'modulo.index.hiddenColumns-{authId}')]
 *     public array $hiddenColumns = [];
 *
 * Ocultar es solo visual (CSS en <x-data-table>): no cambia la consulta ni la exportación.
 */
trait HasColumnVisibility
{
    abstract protected function columns(): array;

    /** Muestra u oculta una columna; siempre queda al menos una visible. */
    public function toggleColumn(string $column): void
    {
        $columns = $this->columns();

        if (! array_key_exists($column, $columns) || ($columns[$column]['hideable'] ?? true) === false) {
            return;
        }

        $hidden = $this->activeHiddenColumns();

        if (in_array($column, $hidden, true)) {
            $this->hiddenColumns = array_values(array_diff($hidden, [$column]));

            return;
        }

        if (count($columns) - count($hidden) <= 1) {
            return;
        }

        $this->hiddenColumns = [...$hidden, $column];

        // Un filtro activo en una columna oculta filtraría sin que el usuario lo vea: se limpia.
        $filter = $columns[$column]['filter'] ?? null;

        if ($filter !== null && $this->{$filter} !== '') {
            $this->reset($filter);
            $this->resetPage();

            if (in_array(HasRowSelection::class, class_uses_recursive($this), true)) {
                $this->clearSelection();
            }
        }
    }

    public function showAllColumns(): void
    {
        $this->hiddenColumns = [];
    }

    /**
     * Columnas ocultas que todavía existen en columns() (la sesión puede traer claves antiguas).
     *
     * @return list<string>
     */
    public function activeHiddenColumns(): array
    {
        return array_values(array_intersect($this->hiddenColumns, array_keys($this->columns())));
    }
}
