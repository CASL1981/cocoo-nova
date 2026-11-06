<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasToggleableStatus;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use Carbon\CarbonImmutable;
use Database\Factories\ProviderLinkageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Vinculación de un proveedor con un proceso (administrativo o comercial).
 *
 * Es la fila de los listados de proveedores de cada módulo: cada proceso la activa,
 * inactiva y negocia sus condiciones de pago sin afectar la vinculación del otro.
 *
 * @property int $id
 * @property int $provider_id
 * @property ProviderScope $scope
 * @property ProviderLinkageStatus $status
 * @property string|null $commercial_segment
 * @property string|null $payment_condition
 * @property string|null $payment_term
 * @property int|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $form_required_at
 * @property string|null $form_required_reason
 * @property-read Provider $provider
 */
final class ProviderLinkage extends Model implements HasToggleableStatus
{
    /** @use HasFactory<ProviderLinkageFactory> */
    use HasFactory;

    use SoftDeletes;
    use Userstamps;

    // Códigos de Characteristic de las condiciones que negocia cada proceso.
    public const COMMERCIAL_SEGMENT_CHARACTERISTIC_CODE = 'provider_commercial_segment';

    public const PAYMENT_CONDITION_CHARACTERISTIC_CODE = 'payment_condition';

    public const PAYMENT_TERM_CHARACTERISTIC_CODE = 'payment_term';

    protected $fillable = [
        'provider_id', 'scope', 'status', 'commercial_segment',
        'payment_condition', 'payment_term', 'form_required_at', 'form_required_reason', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'scope' => ProviderScope::class,
            'status' => ProviderLinkageStatus::class,
            'approved_at' => 'datetime',
            'form_required_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Provider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** Ya fue aprobada alguna vez (Activa o Inactiva). */
    public function isApproved(): bool
    {
        return in_array($this->status, [ProviderLinkageStatus::Active, ProviderLinkageStatus::Inactive], true);
    }

    /** Solo se activa o inactiva una vinculación aprobada; las demás no cambian. */
    public function toggleStatus(): void
    {
        if (! $this->isApproved()) {
            return;
        }

        $this->status = $this->status === ProviderLinkageStatus::Active ? ProviderLinkageStatus::Inactive : ProviderLinkageStatus::Active;
        $this->save();
    }
}
