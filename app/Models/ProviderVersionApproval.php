<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProviderScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Firma de aprobación de una versión por proceso (vinculación administrativa o comercial).
 *
 * Excepción a la regla 5 (sin SoftDeletes ni Userstamps): es en sí misma un registro de
 * auditoría (quién y cuándo) y no se edita ni elimina; "Devolver" a Borrador la descarta.
 *
 * @property int $id
 * @property int $provider_version_id
 * @property ProviderScope $scope
 * @property int|null $user_id
 * @property string|null $user_position
 * @property CarbonImmutable $approved_at
 * @property string|null $observations
 */
final class ProviderVersionApproval extends Model
{
    protected $fillable = ['provider_version_id', 'scope', 'user_id', 'user_position', 'approved_at', 'observations'];

    protected function casts(): array
    {
        return [
            'scope' => ProviderScope::class,
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProviderVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ProviderVersion::class, 'provider_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
