<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Models;

use App\Contracts\Duplicable;
use App\Models\ServicePoint;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Pharmacy\App\Enums\PendingStatus;
use Modules\Pharmacy\App\Enums\PendingType;
use Modules\Pharmacy\Database\Factories\PendingFactory;

/**
 * Encabezado de un pendiente de farmacia (pedido, requisición o mensaje interno).
 *
 * Farmacia lo arma mientras está Activo; al terminarlo queda bloqueado para
 * farmacia y oficina principal gestiona sus ítems.
 *
 * @property int $id
 * @property PendingType $type
 * @property string $category
 * @property int $destination_id
 * @property string|null $reason
 * @property string|null $duration
 * @property string|null $observations
 * @property string|null $eps
 * @property string|null $contracting_modality
 * @property string|null $invoicing_method
 * @property int $user_id
 * @property string|null $manager
 * @property PendingStatus $status
 * @property CarbonImmutable $created_at
 * @property-read ServicePoint|null $destination
 * @property-read User|null $user
 * @property-read Collection<int, PendingItem> $items
 */
final class Pending extends Model implements Duplicable
{
    /** @use HasFactory<PendingFactory> */
    use HasFactory, SoftDeletes, Userstamps;

    /** Códigos de las Characteristics que alimentan los selects; el valor se guarda como texto plano. */
    public const CATEGORY_CHARACTERISTIC_CODE = 'pending_category';

    public const EPS_CHARACTERISTIC_CODE = 'eps';

    public const CONTRACTING_MODALITY_CHARACTERISTIC_CODE = 'contracting_modality';

    public const INVOICING_METHOD_CHARACTERISTIC_CODE = 'invoicing_method';

    protected $table = 'pharmacy_pendings';

    protected $fillable = [
        'type', 'category', 'destination_id', 'reason', 'duration', 'observations',
        'eps', 'contracting_modality', 'invoicing_method', 'user_id', 'manager', 'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => PendingType::class,
            'status' => PendingStatus::class,
        ];
    }

    protected static function newFactory(): PendingFactory
    {
        return PendingFactory::new();
    }

    /**
     * Sede destino; incluye sedes eliminadas para no perder el histórico.
     *
     * @return BelongsTo<ServicePoint, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(ServicePoint::class, 'destination_id')->withTrashed();
    }

    /**
     * Funcionario solicitante.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Ítems (detalle) del pendiente.
     *
     * @return HasMany<PendingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PendingItem::class);
    }

    /**
     * Aplica la preferencia de visualización del usuario:
     * sin sede configurada ve todos los pendientes; con sede, solo los de ese destino.
     * Es una preferencia de consulta, no una restricción de seguridad.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->when(
            $user->pending_service_point_id,
            fn (Builder $q, int $servicePointId) => $q->where('destination_id', $servicePointId)
        );
    }

    /**
     * La copia es un pendiente nuevo: Activo, con quien duplica como solicitante.
     * Los ítems no se copian.
     */
    public function prepareDuplicate(User $actor): void
    {
        $this->user_id = $actor->id;
        $this->status = PendingStatus::Active;
    }

    public function isActive(): bool
    {
        return $this->status === PendingStatus::Active;
    }

    /** Envía la solicitud: farmacia deja de poder editarla. */
    public function finish(): void
    {
        $this->update(['status' => PendingStatus::Finished]);
    }

    /** Reabre una solicitud terminada para que farmacia la corrija. */
    public function reopen(): void
    {
        $this->update(['status' => PendingStatus::Active]);
    }
}
