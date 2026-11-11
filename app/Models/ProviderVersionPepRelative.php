<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IdentityDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Familiar del grupo básico familiar que es PEP (padres, cónyuge, suegros, hijos, yerno/nuera,
 * abuelos, hermanos, cuñados o nietos). Solo documento, nombres y parentesco, como el formato;
 * la persona PEP con quien el proveedor "tiene relación" va en la versión (pep_relation_*).
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
 * @property-read ProviderVersion $version
 */
final class ProviderVersionPepRelative extends Model
{
    protected $fillable = [
        'provider_version_id', 'document_type', 'identification', 'name', 'relationship',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => IdentityDocumentType::class,
        ];
    }

    /** @return BelongsTo<ProviderVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ProviderVersion::class, 'provider_version_id');
    }
}
