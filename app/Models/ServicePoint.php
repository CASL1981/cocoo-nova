<?php

namespace App\Models;

use App\Contracts\Duplicable;
use App\Enums\ServicePointStatus;
use App\Enums\ServicePointType;
use App\Models\Concerns\GeneratesCopyValues;
use Database\Factories\ServicePointFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Catálogo único de sedes/servicios de la organización.
 *
 * Vive en App\Models (no dentro de un módulo de nwidart) a propósito:
 * Farmacia, Administrativo, Talento Humano y Contable lo van a referenciar
 * por igual, así que es dato compartido, no lógica específica de un proceso.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $abbreviation
 * @property ServicePointType $type
 * @property string|null $address
 * @property string|null $phone
 * @property int|null $minimum
 * @property int|null $maximum
 * @property ServicePointStatus $status
 */
class ServicePoint extends Model implements Duplicable
{
    use GeneratesCopyValues;

    /** @use HasFactory<ServicePointFactory> */
    use HasFactory;

    use SoftDeletes;
    use Userstamps;

    /** Código de la Oficina Principal: sus usuarios gestionan pendientes de todas las sedes. */
    public const HEADQUARTERS_CODE = '1';

    protected $fillable = [
        'code', 'name', 'abbreviation', 'type', 'address', 'phone', 'minimum', 'maximum', 'status',
    ];

    /** La copia lleva código y abreviatura con sufijo COPIA y nace inactiva hasta corregirlos. */
    public function prepareDuplicate(User $actor): void
    {
        $this->code = $this->uniqueCopyValue('code', 20);
        $this->abbreviation = $this->uniqueCopyValue('abbreviation', 20);
        $this->status = ServicePointStatus::Inactive;
    }

    protected function casts(): array
    {
        return [
            'type' => ServicePointType::class,
            'status' => ServicePointStatus::class,
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('abbreviation', 'like', "%{$term}%");
        }));
    }

    /** Indica si es la Oficina Principal (código 1). */
    public function isHeadquarters(): bool
    {
        return $this->code === self::HEADQUARTERS_CODE;
    }

    /**
     * Filtra solo los puntos de un tipo dado (ej. solo farmacias).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfType(Builder $query, ServicePointType $type): Builder
    {
        return $query->where('type', $type);
    }
}
