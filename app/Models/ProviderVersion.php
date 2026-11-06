<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyType;
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
 * @property CompanyType|null $company_type
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
        'accepts_information_received', 'accepts_funds_origin',
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
        'minor_representative_identification', 'has_foreign_accounts', 'accepts_electronic_means',
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
        'submitted_by', 'submitted_at', 'verified_by', 'verified_at', 'verification_observations',
        'compliance_by', 'compliance_at', 'compliance_observations',
        'rejected_by', 'rejected_at', 'rejection_reason', 'approved_at',
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
            'company_type' => CompanyType::class,
            'risk_level' => RiskLevel::class,
            'document_issued_on' => 'date',
            'birth_date' => 'date',
            'pep_starts_on' => 'date',
            'pep_ends_on' => 'date',
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

    /** Nombre del archivo PDF del formato de esta versión. */
    public function pdfFileName(): string
    {
        return "FO-300-01-{$this->identification}-v{$this->number}.pdf";
    }
}
