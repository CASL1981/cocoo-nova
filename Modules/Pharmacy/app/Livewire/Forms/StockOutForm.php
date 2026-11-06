<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Livewire\Forms;

use App\Livewire\Concerns\SanitizesPayload;
use App\Models\CharacteristicOption;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Modules\Pharmacy\App\Models\StockOut;

/**
 * Formulario de creación y edición de agotados.
 * El estado no se edita aquí: se gestiona con la acción de activar/inactivar.
 */
final class StockOutForm extends Form
{
    use SanitizesPayload;

    // Agotado en edición; null cuando se crea uno nuevo.
    public ?StockOut $stockOut = null;

    public string $generic_name = '';

    public string $product_name = '';

    public ?string $brand = null;

    public ?string $classification = null;

    public string $circular_number = '';

    public string $circular_date = '';

    public string $generic_code = '';

    /**
     * Carga los datos de un agotado existente en el formulario.
     */
    public function setStockOut(StockOut $stockOut): void
    {
        $this->stockOut = $stockOut;
        $this->fill($stockOut->only([
            'generic_name', 'product_name', 'brand', 'classification', 'circular_number', 'generic_code',
        ]));

        // El input de tipo fecha requiere el formato Y-m-d.
        $this->circular_date = $stockOut->circular_date->format('Y-m-d');
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return self::fieldRules();
    }

    /**
     * Reglas de los campos, compartidas con la importación masiva (StockOutImporter).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function fieldRules(): array
    {
        return [
            'generic_name' => ['required', 'string', 'max:255'],
            'product_name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:150'],
            'classification' => [
                'nullable',
                'string',
                Rule::in(CharacteristicOption::forCharacteristicCode(StockOut::CLASSIFICATION_CHARACTERISTIC_CODE)->keys()),
            ],
            'circular_number' => ['required', 'string', 'max:50'],
            'circular_date' => ['required', 'date', 'before_or_equal:today'],
            'generic_code' => ['required', 'string', 'max:50'],
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
