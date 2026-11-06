<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Actividad económica CIIU Rev. 4 A.C. (DIAN). Catálogo de referencia de solo lectura.
 *
 * @property int $id
 * @property string $code
 * @property string $description
 */
final class EconomicActivity extends Model
{
    public $timestamps = false;

    protected $fillable = ['code', 'description'];

    /** Texto "código - descripción" para buscadores y documentos. */
    public function label(): string
    {
        return "{$this->code} - {$this->description}";
    }

    /**
     * Búsqueda por código o descripción.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('code', 'like', "{$term}%")->orWhere('description', 'like', "%{$term}%"));
    }
}
