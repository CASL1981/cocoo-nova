<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Livewire\Forms;

use App\Enums\ServicePointStatus;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\ServicePoint;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Livewire\Form;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Models\Product;

/**
 * Formulario en línea de ítems de un pendiente.
 * El snapshot del producto, la circular y el estado los asigna SavePendingItem, no el usuario.
 *
 * El usuario elige un genérico (nombre + código) y no una presentación: product_id es el
 * producto representativo del grupo. Al editar, el producto no cambia; si es otro, se
 * elimina el ítem y se agrega el correcto.
 */
final class PendingItemForm extends Form
{
    use SanitizesPayload;

    // Encabezado al que se agregan los ítems; lo asigna la página.
    public ?Pending $pending = null;

    // Ítem en edición; null cuando se agrega uno nuevo.
    public ?PendingItem $item = null;

    public string $product_id = '';

    public ?string $brand = null;

    public string $quantity = '';

    public string $destination_code = '';

    /**
     * Prepara el formulario para un ítem nuevo: el destino inicia con el del encabezado.
     */
    public function setPending(Pending $pending): void
    {
        $this->pending = $pending;
        $this->item = null;
        $this->product_id = '';
        $this->brand = null;
        $this->quantity = '';
        $this->destination_code = (string) $pending->loadMissing('destination')->destination?->code;
        $this->resetValidation();
    }

    /**
     * Carga un ítem existente en el formulario.
     */
    public function setItem(PendingItem $item): void
    {
        $this->item = $item;
        $this->product_id = (string) $item->product_id;
        $this->brand = $item->brand;
        $this->quantity = (string) $item->quantity;
        $this->destination_code = $item->destination_code;
        $this->resetValidation();
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $editing = $this->item !== null;

        return [
            // Al crear: producto activo del catálogo cuyo genérico no esté ya en el pendiente para el mismo destino.
            // Al editar el producto está bloqueado y la repetición se valida contra el C.C.
            'product_id' => $editing ? [] : [
                'required', 'integer',
                Rule::exists(Product::class, 'id')->whereNull('deleted_at')->where('status', ProductStatus::Active->value),
                $this->uniqueGenericRule(),
            ],
            'brand' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999999'],
            'destination_code' => [
                'required', 'string', 'max:20',
                Rule::exists(ServicePoint::class, 'code')->whereNull('deleted_at')->where('status', ServicePointStatus::Active->value),
                ...$this->destinationScopeRule(),
                ...($editing ? [$this->uniqueGenericRule()] : []),
            ],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'destination_code.in' => 'Solo Oficina Principal puede registrar un C.C. distinto al del pendiente.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'product_id' => 'producto',
            'brand' => 'marca',
            'quantity' => 'cantidad',
            'destination_code' => 'C.C.',
        ];
    }

    /**
     * Datos validados y limpios listos para persistir.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }

    /**
     * Un genérico (nombre + código) no se repite en el pendiente para el mismo C.C.
     * Al editar se toma el genérico del ítem; al crear, el del producto elegido.
     */
    private function uniqueGenericRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $generic = $this->item ?? Product::query()->find($this->product_id, ['generic_name', 'generic_code']);

            if ($generic === null) {
                return;
            }

            $repeated = PendingItem::query()
                ->where('pending_id', $this->pending?->id)
                ->where('destination_code', $this->destination_code)
                ->where('generic_name', $generic->generic_name)
                ->where('generic_code', $generic->generic_code)
                ->when($this->item, fn ($query, PendingItem $item) => $query->whereKeyNot($item->id))
                ->exists();

            if ($repeated) {
                $fail('El producto ya está registrado en este pendiente para el mismo C.C.');
            }
        };
    }

    /**
     * Oficina Principal puede registrar cualquier sede; el resto, solo el destino del encabezado.
     *
     * @return array<int, In>
     */
    private function destinationScopeRule(): array
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->worksAtHeadquarters() ? [] : [Rule::in(array_filter([$this->pending?->loadMissing('destination')->destination?->code]))];
    }
}
