<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Enums\ProviderRequestType;
use App\Enums\ProviderVersionStatus;
use App\Enums\ResidenceZone;
use App\Enums\RiskLevel;
use Carbon\CarbonImmutable;
use Database\Factories\ProviderVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Versión del formato FO-300-01 (vinculación o actualización) con la foto completa del formulario.
 *
 * Solo se edita en Borrador; al aprobarse pasa a ser la versión vigente del proveedor y
 * queda inmutable como historial. Sus tablas hijas (contactos, accionistas, familiares PEP
 * y anexos) se copian al crear la siguiente actualización.
 *
 * @property int $id
 * @property int $provider_id
 * @property int $number
 * @property ProviderRequestType $request_type
 * @property ProviderVersionStatus $status
 * @property PersonType $person_type
 * @property IdentityDocumentType $document_type
 * @property string $identification
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property int|null $department_id
 * @property int|null $municipality_id
 * @property int|null $country_id
 * @property int|null $economic_activity_id
 * @property string|null $economic_activity_description
 * @property CarbonImmutable|null $document_issued_on
 * @property int|null $nationality_country_id
 * @property CarbonImmutable|null $birth_date
 * @property int|null $birth_department_id
 * @property int|null $birth_municipality_id
 * @property string|null $education_level
 * @property ResidenceZone|null $residence_zone
 * @property string|null $protection_group
 * @property string|null $protection_group_other
 * @property string|null $occupation
 * @property string|null $occupation_other
 * @property string|null $employer_name
 * @property string|null $employer_activity
 * @property int|null $employer_economic_activity_id
 * @property string|null $minor_representative_name
 * @property string|null $minor_representative_identification
 * @property bool $has_foreign_accounts
 * @property bool $accepts_electronic_means
 * @property string|null $tax_regime
 * @property string|null $company_type
 * @property string|null $legal_representative_name
 * @property IdentityDocumentType|null $legal_representative_document_type
 * @property string|null $legal_representative_identification
 * @property string|null $total_assets
 * @property string|null $total_liabilities
 * @property string|null $total_equity
 * @property bool $in_insolvency
 * @property bool $accepts_risk_bureau
 * @property string|null $monthly_income
 * @property string|null $other_income
 * @property string|null $other_income_activity
 * @property bool $foreign_currency_operations
 * @property string|null $foreign_currency_detail
 * @property bool $manages_public_resources
 * @property bool $declares_pep
 * @property string|null $public_office
 * @property CarbonImmutable|null $pep_starts_on
 * @property CarbonImmutable|null $pep_ends_on
 * @property bool $has_pep_relationship
 * @property string|null $pep_relation_name
 * @property string|null $pep_relation_identification
 * @property string|null $pep_relation_office
 * @property CarbonImmutable|null $pep_relation_starts_on
 * @property CarbonImmutable|null $pep_relation_ends_on
 * @property string|null $pep_relation_relationship
 * @property bool $is_pep
 * @property RiskLevel $risk_level
 * @property string|null $dispatch_schedule
 * @property bool $accepts_truthfulness
 * @property bool $accepts_data_processing
 * @property bool $accepts_information_received
 * @property bool $accepts_funds_origin
 * @property int|null $submitted_by
 * @property CarbonImmutable|null $submitted_at
 * @property int|null $verified_by
 * @property CarbonImmutable|null $verified_at
 * @property string|null $verification_observations
 * @property int|null $compliance_by
 * @property CarbonImmutable|null $compliance_at
 * @property string|null $compliance_observations
 * @property string|null $submitted_position
 * @property string|null $verified_position
 * @property string|null $compliance_position
 * @property int|null $management_by
 * @property string|null $management_position
 * @property bool|null $management_in_system
 * @property CarbonImmutable|null $management_signed_on
 * @property CarbonImmutable|null $management_registered_at
 * @property string|null $management_observations
 * @property int|null $rejected_by
 * @property CarbonImmutable|null $rejected_at
 * @property string|null $rejection_reason
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable $created_at
 * @property-read Provider $provider
 */
final class ProviderVersion extends Model
{
    /** @use HasFactory<ProviderVersionFactory> */
    use HasFactory;

    use SoftDeletes;
    use Userstamps;

    // Códigos de Characteristic de las listas del formulario (hoja "Criterios" del formato).
    public const OCCUPATION_CHARACTERISTIC_CODE = 'provider_occupation';

    public const EDUCATION_LEVEL_CHARACTERISTIC_CODE = 'provider_education_level';

    public const PROTECTION_GROUP_CHARACTERISTIC_CODE = 'provider_protection_group';

    public const RELATIONSHIP_CHARACTERISTIC_CODE = 'provider_relationship';

    public const TAX_REGIME_CHARACTERISTIC_CODE = 'provider_tax_regime';

    /** Tipo de empresa (Pública, Privada, Mixta, Unión temporal...): lo administran los usuarios. */
    public const COMPANY_TYPE_CHARACTERISTIC_CODE = 'provider_company_type';

    /**
     * Campos del formulario que se capturan en pantalla (los de revisión los asigna el servidor).
     *
     * @var list<string>
     */
    public const FORM_FIELDS = [
        'person_type', 'document_type', 'identification', 'name', 'email', 'phone', 'address',
        'department_id', 'municipality_id', 'country_id', 'economic_activity_id', 'economic_activity_description',
        'document_issued_on', 'nationality_country_id', 'birth_date', 'birth_department_id', 'birth_municipality_id',
        'education_level', 'residence_zone', 'protection_group', 'protection_group_other', 'occupation', 'occupation_other',
        'employer_name', 'employer_activity', 'employer_economic_activity_id', 'minor_representative_name',
        'minor_representative_identification', 'has_foreign_accounts', 'accepts_electronic_means',
        'tax_regime', 'company_type', 'legal_representative_name', 'legal_representative_document_type',
        'legal_representative_identification', 'total_assets', 'total_liabilities', 'total_equity', 'in_insolvency',
        'accepts_risk_bureau', 'monthly_income', 'other_income', 'other_income_activity', 'foreign_currency_operations',
        'foreign_currency_detail', 'manages_public_resources', 'declares_pep', 'public_office', 'pep_starts_on',
        'pep_ends_on', 'has_pep_relationship', 'dispatch_schedule', 'accepts_truthfulness', 'accepts_data_processing',
        'accepts_information_received', 'accepts_funds_origin', ...self::PEP_RELATION_FIELDS,
    ];

    /**
     * "¿Tiene relación con PEP?": datos de la persona PEP con quien se relaciona el proveedor
     * (se limpian si la respuesta es No).
     *
     * @var list<string>
     */
    public const PEP_RELATION_FIELDS = [
        'pep_relation_name', 'pep_relation_identification', 'pep_relation_office',
        'pep_relation_starts_on', 'pep_relation_ends_on', 'pep_relation_relationship',
    ];

    /**
     * Campos exclusivos de persona natural (se limpian en una persona jurídica).
     *
     * @var list<string>
     */
    public const NATURAL_FIELDS = [
        'document_issued_on', 'nationality_country_id', 'birth_date', 'birth_department_id', 'birth_municipality_id',
        'education_level', 'residence_zone', 'protection_group', 'protection_group_other', 'occupation', 'occupation_other',
        'employer_name', 'employer_activity', 'employer_economic_activity_id', 'minor_representative_name',
        'minor_representative_identification', 'accepts_electronic_means',
    ];

    /**
     * Campos exclusivos de persona jurídica (se limpian en una persona natural).
     *
     * @var list<string>
     */
    public const LEGAL_FIELDS = [
        'tax_regime', 'company_type', 'legal_representative_name', 'legal_representative_document_type',
        'legal_representative_identification', 'total_assets', 'total_liabilities', 'total_equity', 'in_insolvency',
    ];

    /** Años tras el fin del cargo en que una persona deja de considerarse PEP (Decreto 830 de 2021). */
    public const PEP_VALIDITY_YEARS = 2;

    /**
     * Declaraciones obligatorias para enviar la versión a verificación.
     *
     * @var list<string>
     */
    public const REQUIRED_DECLARATIONS = [
        'accepts_truthfulness', 'accepts_data_processing', 'accepts_information_received', 'accepts_funds_origin',
    ];

    protected $fillable = [
        ...self::FORM_FIELDS,
        'provider_id', 'number', 'request_type', 'status', 'is_pep', 'risk_level',
        'submitted_by', 'submitted_at', 'submitted_position', 'verified_by', 'verified_at', 'verified_position', 'verification_observations',
        'compliance_by', 'compliance_at', 'compliance_position', 'compliance_observations',
        'rejected_by', 'rejected_at', 'rejection_reason', 'approved_at',
        'management_by', 'management_position', 'management_in_system', 'management_signed_on', 'management_registered_at', 'management_observations',
    ];

    /** Campos de las firmas de revisión: se descartan al devolver o corregir la versión. */
    public const REVIEW_RESET = [
        'submitted_by' => null, 'submitted_at' => null, 'submitted_position' => null,
        'verified_by' => null, 'verified_at' => null, 'verified_position' => null, 'verification_observations' => null,
        'compliance_by' => null, 'compliance_at' => null, 'compliance_position' => null, 'compliance_observations' => null,
        'management_by' => null, 'management_position' => null, 'management_in_system' => null, 'management_signed_on' => null,
        'management_registered_at' => null, 'management_observations' => null,
    ];

    protected function casts(): array
    {
        return [
            'request_type' => ProviderRequestType::class,
            'status' => ProviderVersionStatus::class,
            'person_type' => PersonType::class,
            'document_type' => IdentityDocumentType::class,
            'legal_representative_document_type' => IdentityDocumentType::class,
            'residence_zone' => ResidenceZone::class,
            'risk_level' => RiskLevel::class,
            'document_issued_on' => 'date',
            'birth_date' => 'date',
            'pep_starts_on' => 'date',
            'pep_ends_on' => 'date',
            'pep_relation_starts_on' => 'date',
            'pep_relation_ends_on' => 'date',
            'total_assets' => 'decimal:2',
            'total_liabilities' => 'decimal:2',
            'total_equity' => 'decimal:2',
            'monthly_income' => 'decimal:2',
            'other_income' => 'decimal:2',
            'has_foreign_accounts' => 'boolean',
            'accepts_electronic_means' => 'boolean',
            'in_insolvency' => 'boolean',
            'accepts_risk_bureau' => 'boolean',
            'foreign_currency_operations' => 'boolean',
            'manages_public_resources' => 'boolean',
            'declares_pep' => 'boolean',
            'has_pep_relationship' => 'boolean',
            'is_pep' => 'boolean',
            'accepts_truthfulness' => 'boolean',
            'accepts_data_processing' => 'boolean',
            'accepts_information_received' => 'boolean',
            'accepts_funds_origin' => 'boolean',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'compliance_at' => 'datetime',
            'rejected_at' => 'datetime',
            'approved_at' => 'datetime',
            'management_in_system' => 'boolean',
            'management_signed_on' => 'date',
            'management_registered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Provider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /** @return HasMany<ProviderVersionContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(ProviderVersionContact::class);
    }

    /** @return HasMany<ProviderVersionShareholder, $this> */
    public function shareholders(): HasMany
    {
        return $this->hasMany(ProviderVersionShareholder::class);
    }

    /** @return HasMany<ProviderVersionPepRelative, $this> */
    public function pepRelatives(): HasMany
    {
        return $this->hasMany(ProviderVersionPepRelative::class);
    }

    /** @return HasMany<ProviderVersionDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ProviderVersionDocument::class);
    }

    /** @return HasMany<ProviderVersionApproval, $this> */
    public function approvals(): HasMany
    {
        return $this->hasMany(ProviderVersionApproval::class);
    }

    /** @return BelongsTo<GeographicLocation, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(GeographicLocation::class)->withTrashed();
    }

    /** @return BelongsTo<GeographicLocation, $this> */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(GeographicLocation::class)->withTrashed();
    }

    /** @return BelongsTo<GeographicLocation, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(GeographicLocation::class)->withTrashed();
    }

    /** @return BelongsTo<GeographicLocation, $this> */
    public function nationality(): BelongsTo
    {
        return $this->belongsTo(GeographicLocation::class, 'nationality_country_id')->withTrashed();
    }

    /** @return BelongsTo<GeographicLocation, $this> */
    public function birthDepartment(): BelongsTo
    {
        return $this->belongsTo(GeographicLocation::class, 'birth_department_id')->withTrashed();
    }

    /** @return BelongsTo<GeographicLocation, $this> */
    public function birthMunicipality(): BelongsTo
    {
        return $this->belongsTo(GeographicLocation::class, 'birth_municipality_id')->withTrashed();
    }

    /** @return BelongsTo<EconomicActivity, $this> */
    public function economicActivity(): BelongsTo
    {
        return $this->belongsTo(EconomicActivity::class);
    }

    /** @return BelongsTo<EconomicActivity, $this> */
    public function employerEconomicActivity(): BelongsTo
    {
        return $this->belongsTo(EconomicActivity::class, 'employer_economic_activity_id');
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** @return BelongsTo<User, $this> */
    public function complianceOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'compliance_by');
    }

    /**
     * Quien aprobó como Gerencia o registró su firma física.
     *
     * @return BelongsTo<User, $this>
     */
    public function managementSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'management_by');
    }

    /** Tiene registrada la aprobación de Gerencia (en físico o en el sistema). */
    public function hasManagementApproval(): bool
    {
        return $this->management_in_system !== null;
    }

    /** @return BelongsTo<User, $this> */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isDraft(): bool
    {
        return $this->status === ProviderVersionStatus::Draft;
    }

    public function isNatural(): bool
    {
        return $this->person_type === PersonType::Natural;
    }

    /** Tiene cargo público registrado: administra recursos públicos, se declara PEP o ambas. */
    public function hasPublicOffice(): bool
    {
        return $this->manages_public_resources || $this->declares_pep;
    }

    /** Nombre del archivo PDF del formato de esta versión. */
    public function pdfFileName(): string
    {
        return "FO-300-01-{$this->identification}-v{$this->number}.pdf";
    }
}
