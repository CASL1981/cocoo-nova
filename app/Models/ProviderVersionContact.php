<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProviderContactRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contacto del proveedor por cada contacto fijo del formato FO-300-01 (rol), con el cargo del funcionario.
 *
 * Excepción a la regla 5 (sin SoftDeletes ni Userstamps): es parte inmutable de la
 * versión, cuya auditoría cubre el registro completo; se reemplaza al guardar el Borrador.
 *
 * @property int $id
 * @property int $provider_version_id
 * @property ProviderContactRole $role
 * @property string|null $name
 * @property string $job_title
 * @property string|null $phone
 * @property string|null $email
 */
final class ProviderVersionContact extends Model
{
    /** Cargo por defecto cuando el formato no lo trae (el campo es obligatorio). */
    public const DEFAULT_JOB_TITLE = 'No informado';

    protected $fillable = ['provider_version_id', 'role', 'name', 'job_title', 'phone', 'email'];

    /** @var array<string, mixed> */
    protected $attributes = ['job_title' => self::DEFAULT_JOB_TITLE];

    protected function casts(): array
    {
        return [
            'role' => ProviderContactRole::class,
        ];
    }

    /** @return BelongsTo<ProviderVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ProviderVersion::class, 'provider_version_id');
    }
}
