<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProviderContactRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contacto del proveedor por cargo fijo del formato FO-300-01.
 *
 * Excepción a la regla 5 (sin SoftDeletes ni Userstamps): es parte inmutable de la
 * versión, cuya auditoría cubre el registro completo; se reemplaza al guardar el Borrador.
 *
 * @property int $id
 * @property int $provider_version_id
 * @property ProviderContactRole $role
 * @property string|null $name
 * @property string|null $phone
 * @property string|null $email
 */
final class ProviderVersionContact extends Model
{
    protected $fillable = ['provider_version_id', 'role', 'name', 'phone', 'email'];

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
