<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Pharmacy\Database\Factories\InventoryBalanceFactory;
use Modules\Shopping\App\Models\Product;

/**
 * Saldo disponible de un ítem en Oficina Principal según la última carga del ERP.
 *
 * Cada carga reemplaza por completo la anterior. Los ítems de pendientes se cruzan
 * por código genérico: el saldo de un genérico es la suma de (disponible − asignado)
 * de todos los ítems del ERP homologados con ese código.
 *
 * Excepción a la regla de modelos: no lleva SoftDeletes ni Userstamps porque la tabla
 * es una foto que se reemplaza completa; created_by registra quién cargó.
 *
 * @property int $id
 * @property string $item
 * @property string|null $product_name
 * @property int|null $product_id
 * @property string|null $generic_code
 * @property int $available_quantity
 * @property int $assigned_quantity
 * @property CarbonImmutable $cut_off_date
 * @property CarbonImmutable $created_at
 * @property int|null $created_by
 * @property-read Product|null $product
 * @property-read User|null $creator
 */
final class InventoryBalance extends Model
{
    /** @use HasFactory<InventoryBalanceFactory> */
    use HasFactory;

    protected $table = 'pharmacy_inventory_balances';

    protected $fillable = [
        'item', 'product_name', 'product_id', 'generic_code',
        'available_quantity', 'assigned_quantity', 'cut_off_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'available_quantity' => 'integer',
            'assigned_quantity' => 'integer',
            'cut_off_date' => 'date',
        ];
    }

    protected static function newFactory(): InventoryBalanceFactory
    {
        return InventoryBalanceFactory::new();
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Ítems homologados con el catálogo (los únicos que se cruzan con los pendientes).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeMatched(Builder $query): Builder
    {
        return $query->whereNotNull('product_id');
    }

    /**
     * Ítems del ERP que no existen en el catálogo.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnmatched(Builder $query): Builder
    {
        return $query->whereNull('product_id');
    }

    /** Saldo del ítem del ERP: disponible menos lo ya asignado desde la carga. */
    public function balance(): int
    {
        return $this->available_quantity - $this->assigned_quantity;
    }

    /**
     * Saldo por código genérico para los códigos indicados.
     *
     * @param  array<int, string>  $genericCodes
     * @return array<string, int>
     */
    public static function balancesByGeneric(array $genericCodes): array
    {
        if ($genericCodes === []) {
            return [];
        }

        return self::query()->matched()
            ->whereIn('generic_code', array_values(array_unique($genericCodes)))
            ->groupBy('generic_code')
            ->selectRaw('generic_code, SUM(available_quantity - assigned_quantity) as balance')
            ->pluck('balance', 'generic_code')
            ->map(fn (mixed $balance): int => (int) $balance)
            ->all();
    }

    /**
     * Subconsulta de los códigos genéricos con saldo mayor que cero (filtro "solo con stock").
     *
     * @return Builder<self>
     */
    public static function genericCodesWithStock(): Builder
    {
        return self::query()->matched()
            ->select('generic_code')
            ->groupBy('generic_code')
            ->havingRaw('SUM(available_quantity - assigned_quantity) > 0');
    }

    /** Primer registro de la carga vigente (fecha de corte, fecha y usuario de la carga), si existe. */
    public static function currentLoad(): ?self
    {
        return self::query()->with('creator:id,name,lastname')->orderBy('id')->first();
    }
}
