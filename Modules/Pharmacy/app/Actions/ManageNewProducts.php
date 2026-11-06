<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharmacy\App\Enums\NewProductStatus;
use Modules\Pharmacy\App\Models\NewProduct;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Models\Product;

/**
 * Gestión de productos nuevos por Oficina Principal (punto único de sus reglas).
 *
 * - Asociar: enlaza solicitudes abiertas con el ítem que Compras codificó. Cambiar un ítem
 *   ya asociado requiere $canOverwrite (permiso reverse, lo decide la pantalla).
 * - Desasociar: solo solicitudes sin unidades enviadas.
 * - Asignar: manual y sin descontar el saldo del ERP; exige ítem asociado y no supera lo
 *   que falta. Estado: enviado ≥ solicitado → Cumplido; enviado > 0 → Parcial.
 * - Corregir: fija el total enviado (0 vuelve a Sin asignar); no aplica si ya tiene TB.
 * - Rechazar: solo solicitudes Sin asignar sin unidades; Reabrir devuelve una Rechazada a Sin asignar.
 * - TB: numérico, solo solicitudes Cumplidas de una misma farmacia; cambiarlo requiere $canOverwrite.
 *
 * Los errores de negocio se lanzan como ValidationException sobre el campo indicado en cada método.
 */
final class ManageNewProducts
{
    /**
     * @param  list<int>  $ids
     * @return int Solicitudes actualizadas.
     */
    public function link(array $ids, int $productId, bool $canOverwrite): int
    {
        $product = Product::query()->where('status', ProductStatus::Active)->find($productId)
            ?? $this->fail('productId', 'Seleccione un ítem activo del catálogo.');

        return DB::transaction(function () use ($ids, $product, $canOverwrite): int {
            $rows = $this->locked($ids, 'productId');

            if ($rows->contains(fn (NewProduct $row) => ! $row->isOpen())) {
                $this->fail('productId', 'Solo se asocia el ítem a solicitudes Sin asignar o Parciales.');
            }

            if (! $canOverwrite && $rows->contains(fn (NewProduct $row) => $row->product_id !== null && $row->product_id !== $product->id)) {
                $this->fail('productId', 'Algunas solicitudes ya tienen otro ítem asociado: cambiarlo requiere el permiso de reversar.');
            }

            return NewProduct::query()->whereKey($rows->modelKeys())->update(['product_id' => $product->id]);
        });
    }

    /** @param  list<int>  $ids */
    public function unlink(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $rows = $this->locked($ids, 'productId');

            if ($rows->contains(fn (NewProduct $row) => ($row->send_quantity ?? 0) > 0)) {
                $this->fail('productId', 'No se desasocia el ítem de solicitudes con unidades enviadas: corrija primero el enviado.');
            }

            return NewProduct::query()->whereKey($rows->modelKeys())->update(['product_id' => null]);
        });
    }

    public function assign(NewProduct $newProduct, int $quantity): NewProduct
    {
        if ($quantity < 1) {
            $this->fail('quantity', 'La cantidad enviada debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($newProduct, $quantity): NewProduct {
            $row = $this->locked([$newProduct->id], 'quantity')->firstOrFail();

            if (! $row->isOpen()) {
                $this->fail('quantity', 'La solicitud ya no está abierta para asignación.');
            }

            if ($row->product_id === null) {
                $this->fail('quantity', 'Asocie el ítem codificado antes de asignar unidades.');
            }

            $missing = $row->quantity - ($row->send_quantity ?? 0);

            if ($quantity > $missing) {
                $this->fail('quantity', "No puede enviar más de lo que falta ({$missing}).");
            }

            return $this->applyTotal($row, ($row->send_quantity ?? 0) + $quantity, CarbonImmutable::today());
        });
    }

    public function correct(NewProduct $newProduct, int $total): NewProduct
    {
        return DB::transaction(function () use ($newProduct, $total): NewProduct {
            $row = $this->locked([$newProduct->id], 'quantity')->firstOrFail();

            if (($row->send_quantity ?? 0) === 0) {
                $this->fail('quantity', 'Solo se corrige el enviado de solicitudes con unidades asignadas.');
            }

            if ($row->doc_number !== null) {
                $this->fail('quantity', 'La solicitud tiene TB: quítelo antes de corregir el enviado.');
            }

            if ($total < 0 || $total > $row->quantity) {
                $this->fail('quantity', "El total enviado debe estar entre 0 y {$row->quantity}.");
            }

            return $this->applyTotal($row, $total, $row->date_send ?? CarbonImmutable::today());
        });
    }

    /** @param  list<int>  $ids */
    public function reject(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $rows = $this->locked($ids, 'selected');

            if ($rows->contains(fn (NewProduct $row) => $row->status !== NewProductStatus::Unassigned || ($row->send_quantity ?? 0) > 0)) {
                $this->fail('selected', 'Solo se rechazan solicitudes Sin asignar y sin unidades enviadas.');
            }

            return NewProduct::query()->whereKey($rows->modelKeys())->update(['status' => NewProductStatus::Rejected]);
        });
    }

    /** @param  list<int>  $ids */
    public function reopen(array $ids): int
    {
        return DB::transaction(function () use ($ids): int {
            $rows = $this->locked($ids, 'selected');

            if ($rows->contains(fn (NewProduct $row) => $row->status !== NewProductStatus::Rejected)) {
                $this->fail('selected', 'Solo se reabren solicitudes Rechazadas.');
            }

            return NewProduct::query()->whereKey($rows->modelKeys())->update(['status' => NewProductStatus::Unassigned]);
        });
    }

    /** @param  list<int>  $ids */
    public function assignTransferDocument(array $ids, string $docNumber, bool $canOverwrite): int
    {
        $docNumber = trim($docNumber);

        if (preg_match('/^\d{1,50}$/', $docNumber) !== 1) {
            $this->fail('docNumber', 'El número TB debe contener solo números (máximo 50 dígitos).');
        }

        return DB::transaction(function () use ($ids, $docNumber, $canOverwrite): int {
            $rows = $this->fulfilled($ids);

            if ($rows->pluck('destination_code')->unique()->count() > 1) {
                $this->fail('docNumber', 'Un TB es un traslado a una sola farmacia: seleccione solicitudes del mismo C.C. (seleccionados: '
                    .$rows->pluck('destination_code')->unique()->sort()->implode(', ').').');
            }

            if (! $canOverwrite && $rows->contains(fn (NewProduct $row) => $row->doc_number !== null && $row->doc_number !== $docNumber)) {
                $this->fail('docNumber', 'Algunas solicitudes ya tienen TB: cambiarlo requiere el permiso de reversar.');
            }

            return NewProduct::query()->whereKey($rows->modelKeys())->update(['doc_number' => $docNumber]);
        });
    }

    /** @param  list<int>  $ids */
    public function removeTransferDocument(array $ids): int
    {
        return DB::transaction(fn (): int => NewProduct::query()
            ->whereKey($this->fulfilled($ids)->modelKeys())
            ->update(['doc_number' => null]));
    }

    /** Fija el total enviado y recalcula estado y fecha de envío. */
    private function applyTotal(NewProduct $row, int $total, CarbonImmutable $dateSend): NewProduct
    {
        if ($total === 0) {
            $row->update(['send_quantity' => null, 'date_send' => null, 'status' => NewProductStatus::Unassigned]);

            return $row;
        }

        $row->update([
            'send_quantity' => $total,
            'date_send' => $dateSend->toDateString(),
            'status' => $total >= $row->quantity ? NewProductStatus::Fulfilled : NewProductStatus::Partial,
        ]);

        return $row;
    }

    /**
     * Solicitudes seleccionadas (no eliminadas), bloqueadas para la operación.
     *
     * @param  list<int>  $ids
     * @return Collection<int, NewProduct>
     */
    private function locked(array $ids, string $field): Collection
    {
        if ($ids === []) {
            $this->fail($field, 'Seleccione al menos una solicitud.');
        }

        $rows = NewProduct::query()->whereKey($ids)->lockForUpdate()->get();

        if ($rows->count() !== count(array_unique($ids))) {
            $this->fail($field, 'Algunas solicitudes seleccionadas ya no existen.');
        }

        return $rows;
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, NewProduct>
     */
    private function fulfilled(array $ids): Collection
    {
        $rows = $this->locked($ids, 'docNumber');

        if ($rows->contains(fn (NewProduct $row) => $row->status !== NewProductStatus::Fulfilled)) {
            $this->fail('docNumber', 'Solo se asigna TB a solicitudes en estado Cumplido.');
        }

        return $rows;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
