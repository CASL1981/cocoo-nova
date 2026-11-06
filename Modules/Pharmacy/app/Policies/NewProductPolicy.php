<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Policies;

use App\Models\User;
use App\Policies\Concerns\HasStandardModuleAbilities;
use Illuminate\Database\Eloquent\Model;
use Modules\Pharmacy\App\Models\NewProduct;

/**
 * Autorización de productos nuevos (línea de permisos new-products).
 *
 * - Farmacias: ver (read), solicitar (create), editar (update) y eliminar (delete) sus
 *   solicitudes mientras estén Sin asignar y sin ítem asociado; solo las de su propia
 *   sede, salvo Oficina Principal, que abarca todas.
 * - Gestión (solo usuarios de Oficina Principal): asociar ítem, asignar unidades, asignar
 *   TB y rechazar (process); corregir enviado, desasociar, cambiar o quitar TB, reabrir
 *   rechazadas y restaurar eliminadas (reverse).
 */
final class NewProductPolicy
{
    use HasStandardModuleAbilities {
        update as standardUpdate;
        delete as standardDelete;
    }

    protected string $module = 'new-products';

    public function update(User $actor, Model $model): bool
    {
        return $model instanceof NewProduct
            && $model->isEditable()
            && $this->standardUpdate($actor, $model)
            && $this->coversDestination($actor, $model);
    }

    public function delete(User $actor, Model $model): bool
    {
        return $model instanceof NewProduct
            && $model->isEditable()
            && $this->standardDelete($actor, $model)
            && $this->coversDestination($actor, $model);
    }

    /** Pantalla de gestión: lectura y usuario de Oficina Principal. */
    public function manage(User $actor): bool
    {
        return $this->can($actor, 'read') && $actor->worksAtHeadquarters();
    }

    public function process(User $actor): bool
    {
        return $this->can($actor, 'process') && $actor->worksAtHeadquarters();
    }

    public function reverse(User $actor): bool
    {
        return $this->can($actor, 'reverse') && $actor->worksAtHeadquarters();
    }

    /** Restaurar una solicitud eliminada es una corrección de Oficina Principal. */
    public function restore(User $actor, Model $model): bool
    {
        return $this->reverse($actor);
    }

    /** La solicitud es de la sede del usuario, o el usuario es de Oficina Principal. */
    private function coversDestination(User $actor, NewProduct $newProduct): bool
    {
        if ($actor->worksAtHeadquarters()) {
            return true;
        }

        return $actor->loadMissing('servicePoint')->servicePoint?->code === $newProduct->destination_code;
    }
}
