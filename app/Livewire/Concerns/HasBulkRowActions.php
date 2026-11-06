<?php

// app/Livewire/Concerns/HasBulkRowActions.php

namespace App\Livewire\Concerns;

use App\Contracts\HasToggleableStatus;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

trait HasBulkRowActions
{
    use HasRowSelection;

    // Ya NO se declara: protected string $computedRowsProperty = 'items';
    // Cada componente lo expone mediante este método — igual que
    // sortableColumns() en HasSortableColumns. Declarar una propiedad con
    // valor por defecto DENTRO de un trait rompe en cuanto el componente
    // que lo usa necesita un valor distinto (ver el error de $sortBy que
    // ya tuvimos): PHP exige que la declaración sea idéntica en ambos
    // lados, así que propiedades con estado específico de cada pantalla
    // nunca deben vivir en el trait.
    abstract protected function computedRowsProperty(): string;

    public function deleteSelected(): void
    {
        $this->runBulkAction('delete', fn ($row) => $row->delete(), 'eliminado(s)');
    }

    public function toggleSelected(): void
    {
        $this->runBulkAction('toggle', function ($row) {
            if ($row instanceof HasToggleableStatus) {
                $row->toggleStatus();
            }
        }, 'actualizado(s)');
    }

    public function restoreSelected(): void
    {
        $model = $this->rowModelClass();

        $count = $denied = 0;
        foreach ($model::onlyTrashed()->whereKey($this->selectedIds())->get() as $row) {
            if (Gate::allows('restore', $row)) {
                $row->restore();
                $count++;
            } else {
                $denied++;
            }
        }

        $this->finishBulkAction($count, 'restaurado(s)', $denied);
    }

    public function singleActiveSelected(): bool
    {
        if (count($this->selected) !== 1) {
            return false;
        }

        $model = $this->rowModelClass();

        return $model::whereKey($this->selectedIds())->whereNull('deleted_at')->exists();
    }

    /** Clase de la fila: roja si está eliminada, amarilla si está inactiva. */
    public function rowStatusClass(mixed $row): string
    {
        if (! $row instanceof Model) {
            return '';
        }

        if (method_exists($row, 'trashed') && $row->trashed()) {
            return 'table-danger';
        }

        if (isset($row->status) && $row->status instanceof \BackedEnum) {
            return $row->status->value === 'inactive' ? 'table-warning' : '';
        }

        return '';
    }

    abstract protected function rowModelClass(): string;

    /**
     * Ejecuta la acción sobre cada fila seleccionada que la Policy autorice.
     * Las filas no autorizadas se omiten y se informan al usuario con $deniedMessage
     * (o un mensaje genérico), en lugar de fallar en silencio.
     */
    private function runBulkAction(string $ability, Closure $action, string $verb, ?string $deniedMessage = null): void
    {
        $model = $this->rowModelClass();

        $count = $denied = 0;
        foreach ($model::whereKey($this->selectedIds())->get() as $row) {
            if (Gate::allows($ability, $row)) {
                $action($row);
                $count++;
            } else {
                $denied++;
            }
        }

        $this->finishBulkAction($count, $verb, $denied, $deniedMessage);
    }

    private function finishBulkAction(int $count, string $verb, int $denied = 0, ?string $deniedMessage = null): void
    {
        $this->clearSelection();
        unset($this->{$this->computedRowsProperty()});   // ← ahora es llamada de método, con ()

        if ($count > 0 || $denied === 0) {
            $this->toastSuccess("{$count} registro(s) {$verb}");
        }

        if ($denied > 0) {
            $this->toastError("{$denied} registro(s) omitido(s): ".($deniedMessage ?? 'no tienes permiso o su estado no permite esta acción.'));
        }
    }
}
