<?php

namespace App\Models;

use App\Contracts\Duplicable;
use App\Enums\Status;
use App\Models\Concerns\GeneratesCopyValues;
use Database\Factories\CharacteristicOptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Opción de una característica; el texto de `name` es el valor que se guarda en los formularios.
 *
 * @property int $id
 * @property int $characteristic_id
 * @property string $name
 * @property Status $status
 */
class CharacteristicOption extends Model implements Duplicable
{
    /** @use HasFactory<CharacteristicOptionFactory> */
    use GeneratesCopyValues, HasFactory, SoftDeletes, Userstamps;

    protected $fillable = ['characteristic_id', 'name', 'status'];

    /** La copia lleva el nombre con sufijo COPIA (único dentro de su característica) y nace inactiva. */
    public function prepareDuplicate(User $actor): void
    {
        $characteristicId = $this->characteristic_id;
        $this->name = $this->uniqueCopyValue('name', 150, scope: fn (Builder $query) => $query->where('characteristic_id', $characteristicId));
        $this->status = Status::Inactive;
    }

    protected function casts(): array
    {
        return ['status' => Status::class];
    }

    /** @return BelongsTo<Characteristic, $this> */
    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(Characteristic::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where('name', 'like', "%{$term}%"));
    }

    /**
     * Las opciones visibles para un <select>, de la característica identificada por su 'code'.
     *
     * @return Collection<array-key, mixed>
     */
    public static function forCharacteristicCode(string $code): Collection
    {
        return static::query()
            ->whereHas('characteristic', fn (Builder $q) => $q->where('code', $code))
            ->where('status', Status::Active)
            ->pluck('name', 'name');
    }
}
