<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IdentityDocumentType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Familiar o persona relacionada con un PEP declarada en la versión.
 *
 * Excepción a la regla 5 (sin SoftDeletes ni Userstamps): parte de la foto de la versión;
 * solo se agrega o elimina mientras la versión está en Borrador.
 *
 * @property int $id
 * @property int $provider_version_id
 * @property IdentityDocumentType|null $document_type
 * @property string|null $identification
 * @property string $name
 * @property string $relationship
 * @property bool $is_pep
 * @property string|null $public_office
 * @property CarbonImmutable|null $pep_starts_on
 * @property CarbonImmutable|null $pep_ends_on
 * @property-read ProviderVersion $version
 */
final class ProviderVersionPepRelative extends Model
{
    protected $fillable = [
        'provider_version_id', 'document_type', 'identification', 'name', 'relationship',
        'is_pep', 'public_office', 'pep_starts_on', 'pep_ends_on',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => IdentityDocumentType::class,
            'is_pep' => 'boolean',
            'pep_starts_on' => 'date',
            'pep_ends_on' => 'date',
        ];
    }

    /** @return BelongsTo<ProviderVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ProviderVersion::class, 'provider_version_id');
    }
}
