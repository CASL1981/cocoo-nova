<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProviderDocumentType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Anexo de una versión del formato FO-300-01, guardado en el disco privado "local".
 *
 * Excepción a la regla 5 (sin SoftDeletes ni Userstamps): registra quién lo cargó en
 * uploaded_by. Una actualización copia la referencia (el archivo es inmutable), por lo que
 * eliminar el anexo de un Borrador solo borra el archivo si ninguna otra versión lo usa.
 *
 * @property int $id
 * @property int $provider_version_id
 * @property ProviderDocumentType $type
 * @property string $path
 * @property string $original_name
 * @property CarbonImmutable|null $issued_on
 * @property int|null $uploaded_by
 * @property CarbonImmutable $created_at
 * @property-read ProviderVersion $version
 */
final class ProviderVersionDocument extends Model
{
    /** Disco de los anexos: privado, se descargan solo tras autorizar. */
    public const DISK = 'local';

    protected $fillable = ['provider_version_id', 'type', 'path', 'original_name', 'issued_on', 'uploaded_by'];

    protected function casts(): array
    {
        return [
            'type' => ProviderDocumentType::class,
            'issued_on' => 'date',
        ];
    }

    /** @return BelongsTo<ProviderVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ProviderVersion::class, 'provider_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** El certificado de cámara de comercio supera la vigencia máxima de 3 meses. */
    public function isExpired(): bool
    {
        return $this->type->requiresIssueDate()
            && $this->issued_on !== null
            && $this->issued_on->addMonths(ProviderDocumentType::CHAMBER_OF_COMMERCE_VALIDITY_MONTHS)->lt(today());
    }
}
