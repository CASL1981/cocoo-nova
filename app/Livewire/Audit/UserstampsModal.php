<?php

namespace App\Livewire\Audit;

use App\Livewire\Concerns\ManagesModal;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal genérico de auditoría (Userstamps).
 *
 * Muestra quién creó, editó o eliminó un registro, para CUALQUIER modelo
 * de la aplicación que use el trait Mattiverse\Userstamps\Traits\Userstamps.
 * Se invoca pasando el nombre completo de la clase del modelo (modelClass)
 * y su id — así User, Role, ServicePoint y los módulos futuros (Farmacia,
 * Administrativo, etc.) reutilizan este mismo componente, sin un modal
 * de auditoría distinto por cada CRUD.
 */
class UserstampsModal extends Component
{
    use ManagesModal;

    public ?string $createdByName = null;

    public ?string $createdAt = null;

    public bool $wasEdited = false;

    public ?string $updatedByName = null;

    public ?string $updatedAt = null;

    public bool $wasDeleted = false;

    public ?string $deletedByName = null;

    public ?string $deletedAt = null;

    #[On('open-userstamps-audit')]
    public function open(string $modelClass, int $modelId): void
    {
        // El nombre de clase llega desde un evento del navegador: nunca se
        // debe instanciar una cadena arbitraria sin validar que de verdad
        // sea un modelo Eloquent de la aplicación.
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            abort(403);
        }

        // withTrashed(): el registro puede estar eliminado (soft delete) y
        // aun así se debe poder consultar quién y cuándo lo eliminó.
        $model = $modelClass::query()->withoutGlobalScope(SoftDeletingScope::class)->findOrFail($modelId);

        // Reutiliza la misma habilidad 'view' que ya protege el detalle de
        // cada módulo (User, Role, ServicePoint) — nada nuevo que mantener.
        Gate::authorize('view', $model);

        $this->openModal();

        $createdAt = $this->dateOf($model, 'created_at');
        $updatedAt = $this->dateOf($model, 'updated_at');

        $this->createdByName = $this->userNameOf($model, 'creator');
        $this->createdAt = $createdAt?->format('d/m/Y h:i A');

        // Se considera "editado" solo si updated_at es posterior a created_at;
        // al crear un registro, el paquete deja updated_by = created_by,
        // y mostrarlo como "edición" sería confuso para el usuario final.
        $this->wasEdited = $updatedAt && $createdAt && ! $updatedAt->equalTo($createdAt);

        $this->updatedByName = $this->wasEdited ? $this->userNameOf($model, 'editor') : null;
        $this->updatedAt = $this->wasEdited ? $updatedAt?->format('d/m/Y h:i A') : null;

        $this->wasDeleted = method_exists($model, 'trashed') && $model->trashed();
        $this->deletedByName = $this->wasDeleted ? $this->userNameOf($model, 'destroyer') : null;
        $this->deletedAt = $this->wasDeleted ? $this->dateOf($model, 'deleted_at')?->format('d/m/Y h:i A') : null;
    }

    /** Nombre del usuario de una relación de Userstamps (creator, editor o destroyer), si existe. */
    private function userNameOf(Model $model, string $relation): ?string
    {
        $user = $model->getRelationValue($relation);

        return $user instanceof User ? $user->name : null;
    }

    /** Fecha de una columna de auditoría (created_at, updated_at, deleted_at), si existe. */
    private function dateOf(Model $model, string $column): ?CarbonInterface
    {
        $value = $model->getAttribute($column);

        return $value instanceof CarbonInterface ? $value : null;
    }

    protected function resetModalState(): void
    {
        $this->reset([
            'createdByName', 'createdAt',
            'wasEdited', 'updatedByName', 'updatedAt',
            'wasDeleted', 'deletedByName', 'deletedAt',
        ]);
    }

    public function render(): View
    {
        return view('livewire.audit.userstamps-modal');
    }
}
