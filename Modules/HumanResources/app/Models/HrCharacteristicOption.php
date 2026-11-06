<?php

declare(strict_types=1);

namespace Modules\HumanResources\App\Models;

use App\Contracts\Duplicable;
use App\Contracts\HasToggleableStatus;
use App\Enums\Status;
use App\Models\Concerns\GeneratesCopyValues;
use App\Models\Process;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\HumanResources\Database\Factories\HrCharacteristicOptionFactory;

/**
 * Opción de una característica de Talento Humano. En Cargos guarda además el proceso (dirección)
 * y el jefe inmediato, otra opción de la misma característica.
 *
 * @property int $id
 * @property int $hr_characteristic_id
 * @property string $name
 * @property int|null $process_id
 * @property int|null $parent_id
 * @property Status $status
 * @property-read HrCharacteristic|null $characteristic
 * @property-read Process|null $process
 * @property-read HrCharacteristicOption|null $parent
 */
final class HrCharacteristicOption extends Model implements Duplicable, HasToggleableStatus
{
    /** @use HasFactory<HrCharacteristicOptionFactory> */
    use GeneratesCopyValues, HasFactory, SoftDeletes, Userstamps;

    protected $table = 'hr_characteristic_options';

    protected $fillable = ['hr_characteristic_id', 'name', 'process_id', 'parent_id', 'status'];

    protected function casts(): array
    {
        return ['status' => Status::class];
    }

    protected static function newFactory(): HrCharacteristicOptionFactory
    {
        return HrCharacteristicOptionFactory::new();
    }

    /** @return BelongsTo<HrCharacteristic, $this> */
    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(HrCharacteristic::class, 'hr_characteristic_id');
    }

    /** @return BelongsTo<Process, $this> */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class)->withTrashed();
    }

    /** Jefe inmediato (cargos). @return BelongsTo<HrCharacteristicOption, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    /** Cargos que le reportan. @return HasMany<HrCharacteristicOption, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** La copia lleva el nombre con sufijo COPIA (único en su característica) y nace inactiva. */
    public function prepareDuplicate(User $actor): void
    {
        $characteristicId = $this->hr_characteristic_id;
        $this->name = $this->uniqueCopyValue('name', 150, scope: fn (Builder $query) => $query->where('hr_characteristic_id', $characteristicId));
        $this->status = Status::Inactive;
    }

    public function toggleStatus(): void
    {
        $this->status = $this->status === Status::Active ? Status::Inactive : Status::Active;
        $this->save();
    }

    /**
     * @param  Builder<HrCharacteristicOption>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $inner) => $inner->where('name', 'like', "%{$term}%"));
    }

    /**
     * Opciones activas [id => nombre] de la característica con ese código, para los selects.
     *
     * @return array<int, string>
     */
    public static function optionsFor(string $code): array
    {
        return self::query()
            ->whereHas('characteristic', fn (Builder $query) => $query->where('code', $code))
            ->where('status', Status::Active)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * $candidateId es esta misma opción o uno de sus subordinados (directos o indirectos):
     * asignarlo como jefe inmediato crearía un ciclo.
     */
    public function wouldCreateCycle(int $candidateId): bool
    {
        if (! $this->exists) {
            return false;
        }

        $visited = [];
        $current = $candidateId;

        while ($current !== null && ! in_array($current, $visited, true)) {
            if ($current === $this->id) {
                return true;
            }

            $visited[] = $current;
            $parent = self::withTrashed()->whereKey($current)->value('parent_id');
            $current = $parent === null ? null : (int) $parent;
        }

        return false;
    }
}
