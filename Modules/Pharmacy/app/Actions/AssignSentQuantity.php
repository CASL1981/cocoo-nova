<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Models\InventoryBalance;
use Modules\Pharmacy\App\Models\PendingItem;

/**
 * Asignación de cantidades enviadas por Oficina Principal (punto único de sus reglas).
 *
 * - Asignar: lo digitado se suma a lo ya enviado y se descuenta del saldo del código
 *   genérico en la carga de inventario vigente; no se puede asignar más que el saldo.
 * - Estado resultante: enviado ≥ solicitado → Cumplido (fecha de envío y días);
 *   enviado > 0 → Parcial; 0 → Pendiente.
 * - Corregir: fija el total enviado correcto. Un aumento se valida y descuenta del saldo;
 *   una disminución devuelve unidades al saldo solo si la asignación es posterior a la
 *   carga vigente (si es anterior, el archivo del ERP ya la reflejó).
 * - Marcar Pendiente: cambia el estado sin tocar lo ya enviado.
 *
 * Los errores de negocio se lanzan como ValidationException sobre el campo "quantity".
 */
final class AssignSentQuantity
{
    public function assign(PendingItem $item, int $quantity): PendingItem
    {
        if ($quantity < 1) {
            $this->fail('La cantidad enviada debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($item, $quantity): PendingItem {
            $item = $this->lockedItem($item);

            if (! $item->isAssignable()) {
                $this->fail('El ítem ya no está disponible para asignación.');
            }

            $this->consume($item->generic_code, $quantity);

            return $this->applyTotal($item, ($item->send_quantity ?? 0) + $quantity, CarbonImmutable::today());
        });
    }

    public function correct(PendingItem $item, int $total): PendingItem
    {
        if ($total < 0) {
            $this->fail('La cantidad enviada no puede ser negativa.');
        }

        return DB::transaction(function () use ($item, $total): PendingItem {
            $item = $this->lockedItem($item);
            $current = $item->send_quantity ?? 0;

            if ($current === 0 || $item->isOutOfStock()) {
                $this->fail('Solo se corrige el enviado de ítems con cantidad asignada.');
            }

            $difference = $total - $current;

            if ($difference > 0) {
                $this->consume($item->generic_code, $difference);

                return $this->applyTotal($item, $total, CarbonImmutable::today());
            }

            if ($difference < 0 && $this->assignedInCurrentLoad($item)) {
                $this->release($item->generic_code, -$difference);
            }

            return $this->applyTotal($item, $total, $item->date_send ?? CarbonImmutable::today());
        });
    }

    public function markPending(PendingItem $item): PendingItem
    {
        return DB::transaction(function () use ($item): PendingItem {
            $item = $this->lockedItem($item);

            if (! $item->isAssignable()) {
                $this->fail('El ítem ya no está disponible para asignación.');
            }

            $item->update(['status' => PendingItemStatus::Pending]);

            return $item;
        });
    }

    private function lockedItem(PendingItem $item): PendingItem
    {
        return PendingItem::query()->with('pending:id,status')->lockForUpdate()->findOrFail($item->id);
    }

    /** Fija el total enviado y recalcula estado, fecha de envío y días. */
    private function applyTotal(PendingItem $item, int $total, CarbonImmutable $dateSend): PendingItem
    {
        if ($total === 0) {
            $item->update(['send_quantity' => null, 'date_send' => null, 'number_day' => null, 'status' => PendingItemStatus::Pending]);

            return $item;
        }

        $fulfilled = $total >= $item->quantity;

        $item->update([
            'send_quantity' => $total,
            'date_send' => $dateSend->toDateString(),
            // Días entre el registro del ítem y el envío; solo al quedar Cumplido.
            'number_day' => $fulfilled ? (int) abs($item->created_at->startOfDay()->diffInDays($dateSend->startOfDay())) : null,
            'status' => $fulfilled ? PendingItemStatus::Fulfilled : PendingItemStatus::Partial,
        ]);

        return $item;
    }

    /** Descuenta del saldo del genérico, recorriendo sus ítems del ERP en orden. */
    private function consume(string $genericCode, int $quantity): void
    {
        $balances = InventoryBalance::query()->matched()->where('generic_code', $genericCode)
            ->orderBy('id')->lockForUpdate()->get();

        $available = $balances->sum(fn (InventoryBalance $balance) => $balance->balance());

        if ($available < $quantity) {
            $this->fail("Saldo insuficiente: el disponible para el genérico es {$available}.");
        }

        foreach ($balances as $balance) {
            $take = min($balance->balance(), $quantity);

            if ($take > 0) {
                $balance->increment('assigned_quantity', $take);
                $quantity -= $take;
            }

            if ($quantity === 0) {
                break;
            }
        }
    }

    /** Devuelve unidades al saldo del genérico (hasta lo asignado en la carga vigente). */
    private function release(string $genericCode, int $quantity): void
    {
        $balances = InventoryBalance::query()->matched()->where('generic_code', $genericCode)
            ->where('assigned_quantity', '>', 0)->orderByDesc('id')->lockForUpdate()->get();

        foreach ($balances as $balance) {
            $give = min($balance->assigned_quantity, $quantity);
            $balance->decrement('assigned_quantity', $give);
            $quantity -= $give;

            if ($quantity === 0) {
                break;
            }
        }
    }

    /** La última asignación del ítem es del mismo día o posterior a la carga vigente. */
    private function assignedInCurrentLoad(PendingItem $item): bool
    {
        $loadedAt = InventoryBalance::query()->min('created_at');

        return $loadedAt !== null && $item->date_send !== null
            && $item->date_send->greaterThanOrEqualTo(CarbonImmutable::parse($loadedAt)->startOfDay());
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['quantity' => $message]);
    }
}
