<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Livewire\Forms;

use App\Livewire\Concerns\SanitizesPayload;
use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Modules\Pharmacy\App\Models\NewProduct;

/**
 * Formulario de solicitud de producto nuevo.
 * Solicitante, estado, ítem asociado y envíos no se editan aquí: los asigna el servidor.
 */
final class NewProductForm extends Form
{
    use SanitizesPayload;

    // Solicitud en edición; null cuando se crea una nueva.
    public ?NewProduct $newProduct = null;

    public string $product_name = '';

    public ?string $brand = null;

    public string $quantity = '';

    public string $destination_code = '';

    public ?string $observations = null;

    public function setNewProduct(NewProduct $newProduct): void
    {
        $this->newProduct = $newProduct;
        $this->fill($newProduct->only(['product_name', 'brand', 'destination_code', 'observations']));
        $this->quantity = (string) $newProduct->quantity;
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        /** @var User $actor */
        $actor = Auth::user();

        return [
            'product_name' => ['required', 'string', 'min:3', 'max:255'],
            'brand' => ['nullable', 'string', 'max:150'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999999'],
            // Solo sedes no eliminadas y, salvo Oficina Principal, únicamente la sede del usuario.
            'destination_code' => [
                'required', 'string',
                Rule::exists(ServicePoint::class, 'code')->whereNull('deleted_at'),
                ...($actor->worksAtHeadquarters() ? [] : [Rule::in(array_filter([$actor->loadMissing('servicePoint')->servicePoint?->code]))]),
            ],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'product_name' => 'nombre del producto',
            'brand' => 'marca',
            'quantity' => 'cantidad',
            'destination_code' => 'farmacia',
            'observations' => 'observaciones',
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
}
