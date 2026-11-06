<?php

namespace Modules\Shopping\App\Models;

use App\Contracts\Duplicable;
use App\Contracts\HasToggleableStatus;
use App\Models\Concerns\GeneratesCopyValues;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\Database\Factories\ProductFactory;

/**
 * Catálogo de productos (insumos/medicamentos) administrado por el proceso
 * de Compras. Vive en el módulo Shopping porque es lógica/datos exclusivos
 * de ese proceso — a diferencia de ServicePoint, que sí es compartido.
 *
 * @property int $id
 * @property string $item
 * @property string $description
 * @property string|null $brand
 * @property string $generic_name
 * @property string $generic_code
 * @property int|null $supplier_nit NIT del proveedor principal, sin dígito de verificación
 * @property ProductStatus $status
 */
class Product extends Model implements Duplicable, HasToggleableStatus
{
    use GeneratesCopyValues, HasFactory, SoftDeletes, Userstamps;

    protected $table = 'shopping_products';

    protected $fillable = [
        'item', 'description', 'brand', 'atc', 'invima', 'measure_unit',
        'presentation', 'concentration', 'pharmaceutical_form',
        'generic_name', 'generic_code', 'tax_percentage', 'supplier_nit', 'status',
    ];

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    protected function casts(): array
    {
        return [
            'tax_percentage' => 'decimal:2',
            'supplier_nit' => 'integer',
            'status' => ProductStatus::class,
        ];
    }

    /** La copia lleva el ítem con sufijo COPIA y nace inactiva hasta corregir sus datos. */
    public function prepareDuplicate(User $actor): void
    {
        $this->item = $this->uniqueCopyValue('item', 50);
        $this->status = ProductStatus::Inactive;
    }

    public function toggleStatus(): void
    {
        $this->status = $this->status === ProductStatus::Active
            ? ProductStatus::Inactive
            : ProductStatus::Active;
        $this->save();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
            $q->where('item', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('generic_name', 'like', "%{$term}%");
        }));
    }
}
