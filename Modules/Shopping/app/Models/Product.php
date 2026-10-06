<?php

namespace Modules\Shopping\App\Models;

use App\Contracts\HasToggleableStatus;
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
 */
class Product extends Model implements HasToggleableStatus
{
    use HasFactory, SoftDeletes, Userstamps;

    protected $fillable = [
        'item', 'description', 'brand', 'atc', 'invima', 'measure_unit',
        'presentation', 'concentration', 'pharmaceutical_form',
        'generic_name', 'generic_code', 'tax_percentage', 'status',
    ];

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    protected function casts(): array
    {
        return [
            'tax_percentage' => 'decimal:2',
            'status' => ProductStatus::class,
        ];
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
