<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ProviderLegacyUpdateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Actualización del FO-300-01 registrada en el sistema anterior: solo el año y la fecha, sin el
 * formato digital. Se muestra como año informativo junto a las versiones del proveedor.
 *
 * @property int $id
 * @property int $provider_id
 * @property int $year
 * @property CarbonImmutable|null $updated_on
 */
final class ProviderLegacyUpdate extends Model
{
    /** @use HasFactory<ProviderLegacyUpdateFactory> */
    use HasFactory;

    use SoftDeletes;
    use Userstamps;

    protected $fillable = ['provider_id', 'year', 'updated_on'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'updated_on' => 'date'];
    }

    /** @return BelongsTo<Provider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
