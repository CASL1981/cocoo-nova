<?php

namespace App\Models;

use App\Enums\ServicePointStatus;
use App\Enums\ServicePointType;
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
 */

class ServicePoint extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'code', 'name', 'type', 'address', 'phone', 'minimum', 'maximum', 'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => ServicePointType::class,
            'status' => ServicePointStatus::class,
        ];
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%");
        }));
    }

    /** Filtra solo los puntos de un tipo dado (ej. solo farmacias). */
    public function scopeOfType(Builder $query, ServicePointType $type): Builder
    {
        return $query->where('type', $type);
    }
}
