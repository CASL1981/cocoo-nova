<?php

namespace App\Livewire\Concerns;

use App\Contracts\HasToggleableStatus;
use Closure;
use Illuminate\Support\Facades\Gate;

/**
 * Acciones en lote (eliminar / activar-desactivar / restaurar) sobre filas
 * marcadas con checkbox, para cualquier listado que use SoftDeletes y las
 * habilidades estándar de Policy (delete, toggle, restore).
 *
 * El componente anfitrión solo declara:
 *   protected function rowModelClass(): string { return Product::class; }
 *   protected string $computedRowsProperty = 'products'; // nombre del #[Computed]
 *
 * Requiere también el trait Notifies (toastSuccess) en el componente.
 * Incluye HasRowSelection automáticamente — no hace falta declararlo aparte.
 */
trait HasBulkRowActions
{
    use HasRowSelection;

    // protected string $computedRowsProperty = 'items';

    abstract protected function rowModelClass(): string;

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

        $count = 0;
        foreach ($model::onlyTrashed()->whereKey($this->selectedIds())->get() as $row) {
            if (Gate::allows('restore', $row)) {
                $row->restore();
                $count++;
            }
        }

        $this->finishBulkAction($count, 'restaurado(s)');
    }

    /** ¿Hay exactamente UNA fila seleccionada y no está eliminada? Para Editar/Auditar. */
    public function singleActiveSelected(): bool
    {
        if (count($this->selected) !== 1) {
            return false;
        }

        $model = $this->rowModelClass();

        return $model::whereKey($this->selectedIds())->whereNull('deleted_at')->exists();
    }

    /** Clase CSS de la fila: eliminado tiene prioridad sobre inactivo. */
    public function rowStatusClass($row): string
    {
        if (method_exists($row, 'trashed') && $row->trashed()) {
            return 'table-danger';
        }

        if (isset($row->status) && $row->status instanceof \BackedEnum) {
            return $row->status->value === 'inactive' ? 'table-warning' : '';
        }

        return '';
    }

    private function runBulkAction(string $ability, Closure $action, string $verb): void
    {
        $model = $this->rowModelClass();

        $count = 0;
        foreach ($model::whereKey($this->selectedIds())->get() as $row) {
            if (Gate::allows($ability, $row)) {
                $action($row);
                $count++;
            }
        }

        $this->finishBulkAction($count, $verb);
    }

    private function finishBulkAction(int $count, string $verb): void
    {
        $this->clearSelection();
        unset($this->{$this->computedRowsProperty});
        $this->toastSuccess("{$count} registro(s) {$verb}");
    }
}
