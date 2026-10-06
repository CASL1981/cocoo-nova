<?php

namespace Modules\Shopping\App\Livewire\Forms;

use App\Livewire\Concerns\SanitizesPayload;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Models\Product;

class ProductForm extends Form
{
    use SanitizesPayload;

    public ?Product $product = null;

    public string $item = '';
    public string $description = '';
    public string $brand = '';
    public string $atc = '';
    public string $invima = '';
    public string $measure_unit = '';
    public string $presentation = '';
    public string $concentration = '';
    public string $pharmaceutical_form = '';
    public string $generic_name = '';
    public string $generic_code = '';
    public string $tax_percentage = '';
    public string $status = 'active';

    protected function rules(): array
    {
        return [
            'item' => ['required', 'string', 'max:50', Rule::unique('products', 'item')->ignore($this->product)],
            'description' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:150'],
            'atc' => ['nullable', 'string', 'max:20'],
            'invima' => ['nullable', 'string', 'max:50'],
            'measure_unit' => ['required', 'string', 'max:50'],
            'presentation' => ['nullable', 'string', 'max:150'],
            'concentration' => ['nullable', 'string', 'max:150'],
            'pharmaceutical_form' => ['nullable', 'string', 'max:150'],
            'generic_name' => ['required', 'string', 'max:150'],
            'generic_code' => ['required', 'string', 'max:50'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
        ];
    }

    /** Campos de texto libre a recorrer al poblar/mapear el formulario (todos excepto status). */
    private const TEXT_FIELDS = [
        'item', 'description', 'brand', 'atc', 'invima', 'measure_unit',
        'presentation', 'concentration', 'pharmaceutical_form',
        'generic_name', 'generic_code', 'tax_percentage',
    ];

    public function setProduct(Product $product): void
    {
        $this->product = $product;

        foreach (self::TEXT_FIELDS as $field) {
            $this->{$field} = (string) ($product->{$field} ?? '');
        }

        $this->status = $product->status->value;
    }

    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }
}
