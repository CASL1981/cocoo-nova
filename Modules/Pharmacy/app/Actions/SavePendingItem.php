<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Actions;

use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;
use Modules\Pharmacy\App\Models\StockOut;
use Modules\Shopping\App\Models\Product;

/**
 * Crea o actualiza un ítem de pendiente (punto único de sus reglas de negocio).
 *
 * - Al crear copia un snapshot del genérico elegido: product_id es el producto
 *   representativo del grupo y product_name su descripción; la pantalla muestra
 *   el nombre genérico (el usuario no elige presentación).
 * - Al editar el producto no cambia: si es otro, se elimina el ítem y se agrega el correcto.
 * - Si el código genérico tiene un agotado activo, el ítem queda Agotado con su
 *   número de circular; Agotado es definitivo (la Policy impide editarlo).
 * - En otro caso queda Sin revisión; Cumplido/Parcial/Pendiente los asigna Oficina Principal.
 */
final class SavePendingItem
{
    /** @param  array<string, mixed>  $data  product_id (solo al crear), brand, quantity, destination_code */
    public function execute(array $data, Pending $pending, ?PendingItem $item = null): PendingItem
    {
        $productId = $data['product_id'] ?? null;
        unset($data['product_id']);

        $snapshot = [];

        if ($item === null) {
            $product = Product::query()->whereKey($productId)->firstOrFail();
            $snapshot = [
                'product_id' => $product->id,
                'product_name' => $product->description,
                'generic_code' => $product->generic_code,
                'generic_name' => $product->generic_name,
            ];
        }

        $genericCode = $item === null ? $snapshot['generic_code'] : $item->generic_code;
        $stockOut = StockOut::active()->where('generic_code', $genericCode)->latest('id')->first();

        $attributes = [
            ...$data,
            ...$snapshot,
            'circular_number' => $stockOut?->circular_number,
            'status' => $stockOut ? PendingItemStatus::OutOfStock : PendingItemStatus::Unreviewed,
        ];

        if ($item) {
            $item->update($attributes);

            return $item;
        }

        return $pending->items()->create($attributes);
    }
}
