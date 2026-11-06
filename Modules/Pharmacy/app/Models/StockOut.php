<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Models;

use App\Contracts\Duplicable;
use App\Contracts\HasToggleableStatus;
use App\Enums\Status;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Pharmacy\Database\Factories\StockOutFactory;

// use Modules\Pharmacy\Database\Factories\StockOutFactory;

/**
 * Producto agotado reportado mediante circular.
 *
 * Mientras esté activo, las líneas de solicitud con el mismo generic_code
 * se marcan automáticamente como Agotado.
 *
 * @property int $id
 * @property string $generic_name
 * @property string $product_name
 * @property string|null $brand
 * @property string|null $classification
 * @property string|null $circular_number
 * @property CarbonImmutable $circular_date
 * @property string $generic_code
 * @property Status $status
 */
final class StockOut extends Model implements Duplicable, HasToggleableStatus
{
    use HasFactory, SoftDeletes, Userstamps;

    /**
     * Código de la Characteristic cuyas opciones alimentan el select de "Clasificación".
     * El valor seleccionado se guarda como texto plano en `classification` (no es FK).
     */
    public const CLASSIFICATION_CHARACTERISTIC_CODE = 'stock_out_classification';

    /**
     * Tabla asociada al módulo Pharmacy y al modelo StockOut.
     */
    protected $table = 'pharmacy_stock_outs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'generic_name', 'product_name', 'brand', 'classification',
        'circular_number', 'circular_date', 'generic_code', 'status',
    ];

    protected function casts(): array
    {
        return [
            'circular_date' => 'date',
            'status' => Status::class,
        ];
    }

    protected static function newFactory(): StockOutFactory
    {
        return StockOutFactory::new();
    }

    /**
     * Agotados vigentes: los únicos que bloquean líneas de solicitud.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /** La copia nace inactiva para no marcar ítems como agotados antes de revisarla. */
    public function prepareDuplicate(User $actor): void
    {
        $this->status = Status::Inactive;
    }

    /**
     * Alterna entre activo e inactivo (usado por las acciones masivas).
     */
    public function toggleStatus(): void
    {
        $this->status = $this->status === Status::Active ? Status::Inactive : Status::Active;
        $this->save();
    }
}
