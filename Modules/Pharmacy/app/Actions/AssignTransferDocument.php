<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Models\PendingItem;

/**
 * Asignación del número TB (traslado de bodega del ERP) a ítems Cumplidos.
 *
 * - Solo ítems Cumplido: los Parciales todavía no se han despachado completos.
 * - Un TB es un traslado a una sede: todos los ítems deben tener el mismo C.C.
 * - El TB es numérico (doc_number, hasta 50 dígitos).
 * - Cambiar o quitar un TB ya asignado es una corrección: requiere $canOverwrite
 *   (permiso inventory-balances-reverse, lo decide la pantalla).
 *
 * Los errores de negocio se lanzan como ValidationException sobre el campo "docNumber".
 */
final class AssignTransferDocument
{
    /**
     * @param  list<int>  $itemIds
     * @return int Ítems actualizados.
     */
    public function assign(array $itemIds, string $docNumber, bool $canOverwrite): int
    {
        $docNumber = trim($docNumber);

        if (preg_match('/^\d{1,50}$/', $docNumber) !== 1) {
            $this->fail('El número TB debe contener solo números (máximo 50 dígitos).');
        }

        return DB::transaction(function () use ($itemIds, $docNumber, $canOverwrite): int {
            $items = $this->lockedItems($itemIds);

            if ($items->pluck('destination_code')->unique()->count() > 1) {
                $this->fail('Un TB es un traslado a una sola sede: seleccione ítems del mismo C.C. (seleccionados: '
                    .$items->pluck('destination_code')->unique()->sort()->implode(', ').').');
            }

            if (! $canOverwrite && $items->contains(fn (PendingItem $item) => $item->doc_number !== null && $item->doc_number !== $docNumber)) {
                $this->fail('Algunos ítems ya tienen TB: cambiarlo requiere el permiso de reversar.');
            }

            return PendingItem::query()->whereKey($items->modelKeys())->update(['doc_number' => $docNumber]);
        });
    }

    /**
     * Quita el TB de los ítems (corrección).
     *
     * @param  list<int>  $itemIds
     */
    public function remove(array $itemIds): int
    {
        return DB::transaction(fn (): int => PendingItem::query()
            ->whereKey($this->lockedItems($itemIds)->modelKeys())
            ->update(['doc_number' => null]));
    }

    /**
     * Ítems seleccionados, bloqueados y validados como Cumplidos.
     *
     * @param  list<int>  $itemIds
     * @return Collection<int, PendingItem>
     */
    private function lockedItems(array $itemIds): Collection
    {
        if ($itemIds === []) {
            $this->fail('Seleccione al menos un ítem.');
        }

        $items = PendingItem::query()->whereKey($itemIds)->lockForUpdate()->get();

        if ($items->count() !== count(array_unique($itemIds))
            || $items->contains(fn (PendingItem $item) => $item->status !== PendingItemStatus::Fulfilled)) {
            $this->fail('Solo se asigna TB a ítems en estado Cumplido.');
        }

        return $items;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['docNumber' => $message]);
    }
}
