<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Enums\ProviderFormStatus;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Enums\RiskLevel;
use Carbon\CarbonImmutable;
use Database\Factories\ProviderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Proveedor (tercero) transversal, único por identificación.
 *
 * Guarda los datos básicos (contacto y ubicación) y el resumen de la versión vigente del
 * FO-300-01 (riesgo, PEP, próxima actualización). El formato solo lo diligencian los
 * proveedores con compras mayores a 2 SMMLV y los comerciales; los demás son "básicos".
 * Cada proceso que le compra tiene su propia vinculación (ProviderLinkage).
 *
 * @property int $id
 * @property PersonType $person_type
 * @property IdentityDocumentType $document_type
 * @property string $identification
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property int|null $municipality_id
 * @property string|null $contact_name
 * @property int|null $current_version_id
 * @property RiskLevel|null $risk_level
 * @property bool $is_pep
 * @property CarbonImmutable|null $next_update_on
 */
final class Provider extends Model
{
    /** @use HasFactory<ProviderFactory> */
    use HasFactory;

    use SoftDeletes;
    use Userstamps;

    /** Meses de vigencia de una versión aprobada antes de exigir la actualización anual. */
    public const UPDATE_PERIOD_MONTHS = 12;

    protected $fillable = [
        'person_type', 'document_type', 'identification', 'name', 'email', 'phone', 'address',
        'municipality_id', 'contact_name', 'current_version_id', 'risk_level', 'is_pep', 'next_update_on',
    ];

    protected function casts(): array
    {
        return [
            'person_type' => PersonType::class,
            'document_type' => IdentityDocumentType::class,
            'risk_level' => RiskLevel::class,
            'is_pep' => 'boolean',
            'next_update_on' => 'date',
        ];
    }

    /** @return BelongsTo<GeographicLocation, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(GeographicLocation::class, 'municipality_id')->withTrashed();
    }

    /** @return HasMany<ProviderVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ProviderVersion::class);
    }

    /**
     * Versión aprobada vigente.
     *
     * @return BelongsTo<ProviderVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ProviderVersion::class, 'current_version_id');
    }

    /**
     * Versión en trámite (Borrador o en revisión); como máximo hay una.
     *
     * @return HasOne<ProviderVersion, $this>
     */
    public function openVersion(): HasOne
    {
        return $this->hasOne(ProviderVersion::class)->whereIn('status', ProviderVersionStatus::open())->latestOfMany('number');
    }

    /**
     * Última versión del formato (cualquier estado): la rechazada se corrige sobre ella.
     *
     * @return HasOne<ProviderVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(ProviderVersion::class)->latestOfMany('number');
    }

    /** @return HasMany<ProviderLinkage, $this> */
    public function linkages(): HasMany
    {
        return $this->hasMany(ProviderLinkage::class);
    }

    /**
     * Años en que el proveedor actualizó en el sistema anterior (solo fecha, sin formato digital).
     *
     * @return HasMany<ProviderLegacyUpdate, $this>
     */
    public function legacyUpdates(): HasMany
    {
        return $this->hasMany(ProviderLegacyUpdate::class);
    }

    /**
     * Regla anual: un proveedor tiene como máximo una solicitud de FO-300-01 por año calendario
     * (vinculación o actualización). Si se rechaza, se corrige esa misma versión.
     */
    public function hasVersionInYear(int $year): bool
    {
        return $this->versions()->whereYear('created_at', $year)->exists();
    }

    /**
     * Versión sobre la que se firma: la que está en trámite o, si una vinculación espera aprobación
     * sin versión nueva (otro proceso se vinculó en el mismo año del formato), la vigente.
     * Requiere openVersion, currentVersion y linkages cargadas.
     */
    public function reviewVersion(): ?ProviderVersion
    {
        if ($this->openVersion !== null) {
            return $this->openVersion;
        }

        $waiting = $this->linkages->contains(fn (ProviderLinkage $linkage): bool => $linkage->status === ProviderLinkageStatus::Pending);

        return $waiting ? $this->currentVersion : null;
    }

    /**
     * Proveedores con una vinculación activa en el proceso indicado (para OC y listas de precios).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActiveFor(Builder $query, ProviderScope $scope): Builder
    {
        return $query->whereHas('linkages', fn (Builder $q) => $q->where('scope', $scope)->where('status', ProviderLinkageStatus::Active));
    }

    /**
     * Situación frente al FO-300-01. Requiere la relación openVersion cargada (o la consulta).
     */
    public function formStatus(): ProviderFormStatus
    {
        if ($this->current_version_id === null) {
            return $this->openVersion !== null ? ProviderFormStatus::InProgress : ProviderFormStatus::None;
        }

        return $this->updateIsDue() ? ProviderFormStatus::Expired : ProviderFormStatus::Current;
    }

    /**
     * La identificación ya no se cambia: el proveedor tiene un formato aprobado o una vinculación
     * activa (proveedor básico ya en uso).
     */
    public function isIdentityLocked(): bool
    {
        return $this->current_version_id !== null
            || $this->linkages()->whereIn('status', [ProviderLinkageStatus::Active, ProviderLinkageStatus::Inactive])->exists();
    }

    /** La actualización anual del FO-300-01 está vencida. */
    public function updateIsDue(): bool
    {
        return $this->next_update_on !== null && $this->next_update_on->lte(today());
    }
}
