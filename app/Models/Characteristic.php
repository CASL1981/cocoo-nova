<?php

namespace App\Models;

use App\Contracts\Duplicable;
use App\Enums\Status;
use App\Models\Concerns\GeneratesCopyValues;
use Database\Factories\CharacteristicFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Lista configurable (ej. EPS, categorías) cuyas opciones alimentan los selects de la aplicación.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property Status $status
 */
class Characteristic extends Model implements Duplicable
{
    /** @use HasFactory<CharacteristicFactory> */
    use GeneratesCopyValues, HasFactory, SoftDeletes, Userstamps;

    protected $fillable = ['name', 'code', 'status'];

    /**
     * La copia lleva el código con sufijo "_copia" (respeta el formato snake_case)
     * y nace inactiva. Sus opciones no se copian.
     */
    public function prepareDuplicate(User $actor): void
    {
        $this->code = $this->uniqueCopyValue('code', 100, '_copia');
        $this->status = Status::Inactive;
    }

    protected function casts(): array
    {
        return ['status' => Status::class];
    }

    /** @return HasMany<CharacteristicOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(CharacteristicOption::class);
    }

    /**
     * El código ya no se cambia cuando la característica tiene opciones (incluidas las eliminadas):
     * la aplicación busca sus listas por código (ej. payment_condition) y quedarían vacías.
     */
    public function isCodeLocked(): bool
    {
        return $this->exists && $this->options()->withTrashed()->exists();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%");
        }));
    }
}
