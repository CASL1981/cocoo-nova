<?php

namespace App\Livewire\Concerns;

use App\Actions\DuplicateRecord;
use App\Contracts\Duplicable;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use LogicException;

/**
 * Botón "Duplicar" de los listados: copia directa del registro con DuplicateRecord.
 *
 * Autoriza con la habilidad "duplicate" de la Policy (mismo permiso de crear
 * más las reglas propias del modelo). Requiere Notifies y, para duplicateSelected(),
 * HasRowSelection; el modelo debe implementar App\Contracts\Duplicable.
 */
trait DuplicatesRows
{
    abstract protected function rowModelClass(): string;

    abstract protected function computedRowsProperty(): string;

    /** Duplica el único registro seleccionado y deja seleccionada la copia, lista para editar. */
    public function duplicateSelected(): void
    {
        if ($this->selectAllMatching || count($this->selected) !== 1) {
            return;
        }

        $copy = $this->duplicateRecord($this->selectedIds()[0]);

        if ($copy) {
            $this->selected = [(string) $copy->getKey()];
        }
    }

    /**
     * Crea la copia del registro (los eliminados no se duplican), refresca la
     * tabla y notifica el número del registro nuevo.
     */
    protected function duplicateRecord(int $id): ?Model
    {
        $model = $this->rowModelClass();
        $source = $model::query()->find($id);

        if (! $source instanceof Model) {
            $this->toastError('Los registros eliminados no se pueden duplicar.');

            return null;
        }

        Gate::authorize('duplicate', $source);

        if (! $source instanceof Duplicable) {
            throw new LogicException("{$model} no implementa Duplicable.");
        }

        /** @var User $actor */
        $actor = Auth::user();

        try {
            $copy = app(DuplicateRecord::class)->execute($source, $actor);
        } catch (QueryException $e) {
            Log::error('records.duplicate_failed', ['model' => $model, 'id' => $id, 'error' => $e->getMessage()]);
            $this->toastError('No se pudo duplicar el registro. Inténtalo de nuevo.');

            return null;
        }

        unset($this->{$this->computedRowsProperty()});

        $inactive = $copy->getAttribute('status') instanceof BackedEnum && $copy->getAttribute('status')->value === 'inactive';
        $this->toastSuccess("Registro N° {$copy->getKey()} creado como copia del N° {$id}."
            .($inactive ? ' Quedó inactivo: revise sus datos y actívelo.' : ''));

        return $copy;
    }
}
