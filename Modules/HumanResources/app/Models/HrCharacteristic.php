<?php

declare(strict_types=1);

namespace Modules\HumanResources\App\Models;

use App\Contracts\Duplicable;
use App\Contracts\HasToggleableStatus;
use App\Enums\Status;
use App\Models\Concerns\GeneratesCopyValues;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\HumanResources\Database\Factories\HrCharacteristicFactory;

/**
 * Lista configurable de Talento Humano (ej. Cargos). Equivale a las características generales,
 * pero separada para las particularidades del área.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property Status $status
 */
final class HrCharacteristic extends Model implements Duplicable, HasToggleableStatus
{
    /** @use HasFactory<HrCharacteristicFactory> */
    use GeneratesCopyValues, HasFactory, SoftDeletes, Userstamps;

    /** Código de la característica de cargos del organigrama: sus opciones llevan proceso y jefe inmediato. */
    public const POSITIONS_CODE = 'positions';

    protected $table = 'hr_characteristics';

    protected $fillable = ['name', 'code', 'status'];

    protected function casts(): array
    {
        return ['status' => Status::class];
    }

    protected static function newFactory(): HrCharacteristicFactory
    {
        return HrCharacteristicFactory::new();
    }

    /** @return HasMany<HrCharacteristicOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(HrCharacteristicOption::class);
    }

    /** Sus opciones llevan proceso y jefe inmediato (característica de cargos). */
    public function isPositions(): bool
    {
        return $this->code === self::POSITIONS_CODE;
    }

    /**
     * El código no cambia cuando la característica tiene opciones (incluidas las eliminadas):
     * la aplicación busca sus listas por código y quedarían vacías.
     */
    public function isCodeLocked(): bool
    {
        return $this->exists && $this->options()->withTrashed()->exists();
    }

    /** La copia lleva el código con sufijo "_copia" y nace inactiva; sus opciones no se copian. */
    public function prepareDuplicate(User $actor): void
    {
        $this->code = $this->uniqueCopyValue('code', 100, '_copia');
        $this->status = Status::Inactive;
    }

    public function toggleStatus(): void
    {
        $this->status = $this->status === Status::Active ? Status::Inactive : Status::Active;
        $this->save();
    }

    /**
     * Búsqueda por nombre o código.
     *
     * @param  Builder<HrCharacteristic>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $inner) => $inner->where(fn (Builder $where) => $where
            ->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%")));
    }
}
