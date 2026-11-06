<?php

namespace Modules\Shopping\App\Imports;

use App\Contracts\RowImporter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\Shopping\App\Actions\SaveProduct;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Livewire\Forms\ProductForm;
use Modules\Shopping\App\Models\Product;

/**
 * Importación masiva del catálogo de productos (pantalla de Productos y ProductSeeder).
 *
 * Clave: item. Un producto existente se actualiza aunque esté eliminado
 * lógicamente (sigue eliminado; restaurarlo es una acción aparte).
 */
final class ProductImporter implements RowImporter
{
    public function __construct(private readonly SaveProduct $saveProduct = new SaveProduct) {}

    public function modelClass(): string
    {
        return Product::class;
    }

    public function columns(): array
    {
        return [
            'item' => 'Ítem',
            'description' => 'Descripción',
            'brand' => 'Marca',
            'atc' => 'ATC',
            'invima' => 'INVIMA',
            'measure_unit' => 'Unidad de medida',
            'presentation' => 'Presentación',
            'concentration' => 'Concentración',
            'pharmaceutical_form' => 'Forma farmacéutica',
            'generic_name' => 'Nombre genérico',
            'generic_code' => 'Código genérico',
            'tax_percentage' => '% Impuesto',
            'supplier_nit' => 'NIT proveedor',
            'status' => 'Estado',
        ];
    }

    public function key(): string
    {
        return 'item';
    }

    public function rules(): array
    {
        // El estado es opcional: vacío crea el producto activo y no cambia uno existente.
        return [...ProductForm::fieldRules(), 'status' => ['nullable', Rule::enum(ProductStatus::class)]];
    }

    /** Acepta el estado como valor (active) o como etiqueta (Activo), igual que la exportación. */
    public function prepare(array $row): array
    {
        if (is_string($row['status'] ?? null)) {
            $byLabel = array_change_key_case(array_flip(ProductStatus::options()));
            $row['status'] = $byLabel[mb_strtolower($row['status'])] ?? mb_strtolower($row['status']);
        }

        return $row;
    }

    public function save(array $data, ?Model $existing): void
    {
        if (($data['status'] ?? null) === null) {
            unset($data['status']);
        }

        $this->saveProduct->execute($data, $existing instanceof Product ? $existing : null);
    }
}
