<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IdentityDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Propietario, consorciado o accionista con participación de 5 % o más (persona jurídica).
 *
 * Excepción a la regla 5 (sin SoftDeletes ni Userstamps): parte de la foto de la versión;
 * solo se agrega o elimina mientras la versión está en Borrador.
 *
 * @property int $id
 * @property int $provider_version_id
 * @property IdentityDocumentType $document_type
 * @property string $identification
 * @property string $name
 * @property string|null $legal_representative_name
 * @property string|null $legal_representative_identification
 * @property string $ownership_percentage
 * @property-read ProviderVersion $version
 */
final class ProviderVersionShareholder extends Model
{
    /** Participación mínima que obliga a registrar al accionista. */
    public const MIN_OWNERSHIP_PERCENTAGE = 5;

    protected $fillable = [
        'provider_version_id', 'document_type', 'identification', 'name',
        'legal_representative_name', 'legal_representative_identification', 'ownership_percentage',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => IdentityDocumentType::class,
            'ownership_percentage' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<ProviderVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ProviderVersion::class, 'provider_version_id');
    }
}
