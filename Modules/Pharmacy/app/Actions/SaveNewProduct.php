<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Modules\Pharmacy\App\Enums\NewProductStatus;
use Modules\Pharmacy\App\Models\NewProduct;

/**
 * Crea o actualiza una solicitud de producto nuevo (punto único de sus reglas de registro).
 *
 * Bloqueo de duplicados por nombre normalizado (sin tildes, mayúsculas ni espacios repetidos):
 * - Si una solicitud con ese nombre ya tiene ítem asociado, el producto ya está codificado
 *   y debe pedirse por Pendientes.
 * - No se repite una solicitud abierta (Sin asignar o Parcial) del mismo nombre para la misma farmacia.
 *
 * Al crear, el solicitante es el usuario autenticado y el estado inicia en Sin asignar.
 * Los errores de negocio se lanzan como ValidationException sobre el campo "product_name".
 */
final class SaveNewProduct
{
    /** @param  array<string, mixed>  $data  product_name, brand, quantity, destination_code, observations */
    public function execute(array $data, User $actor, ?NewProduct $newProduct = null): NewProduct
    {
        $this->ensureNotDuplicated((string) $data['product_name'], (string) $data['destination_code'], $newProduct);

        if ($newProduct) {
            $newProduct->update($data);

            return $newProduct;
        }

        return NewProduct::create([
            ...$data,
            'user_id' => $actor->id,
            'status' => NewProductStatus::Unassigned,
        ]);
    }

    private function ensureNotDuplicated(string $name, string $destinationCode, ?NewProduct $current): void
    {
        $normalized = NewProduct::normalizeName($name);

        $linked = NewProduct::query()->with('product:id,item,description')
            ->where('normalized_name', $normalized)->whereNotNull('product_id')->first();

        if ($linked) {
            $this->fail("Este producto ya está codificado como ítem {$linked->product?->item} ({$linked->product?->description}): solicítelo en Pendientes.");
        }

        $open = NewProduct::query()->open()
            ->where('normalized_name', $normalized)
            ->where('destination_code', $destinationCode)
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->first();

        if ($open) {
            $this->fail("Ya existe una solicitud abierta (N° {$open->id}) de este producto para la farmacia {$destinationCode}.");
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['product_name' => $message]);
    }
}
