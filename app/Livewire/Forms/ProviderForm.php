<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\CompanyType;
use App\Enums\GeographicLevel;
use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Enums\ProviderContactRole;
use App\Enums\ResidenceZone;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\CharacteristicOption;
use App\Models\EconomicActivity;
use App\Models\GeographicLocation;
use App\Models\Provider;
use App\Models\ProviderVersion;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Livewire\Form;

/**
 * Formulario de una versión del formato FO-300-01 (persona natural o jurídica).
 *
 * Los campos obligatorios dependen del tipo de persona y de las respuestas (ocupación
 * "Otro", PEP, moneda extranjera, menor de edad). Las declaraciones se exigen al enviar
 * a verificación (ProviderWorkflow::submit), no al guardar el Borrador.
 */
final class ProviderForm extends Form
{
    use SanitizesPayload;

    /** Valor de las listas de Características que obliga a especificar "¿Cuál?". */
    public const OTHER_OPTION = 'Otro';

    // Versión en edición; null al registrar un proveedor nuevo.
    public ?ProviderVersion $version = null;

    // Proveedor aprobado o ya en uso: su identificación y tipo de persona no se editan.
    public bool $identityLocked = false;

    public string $person_type = 'legal';

    public string $document_type = 'NIT';

    public string $identification = '';

    public string $name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $department_id = null;

    public ?string $municipality_id = null;

    public ?string $country_id = null;

    public ?string $economic_activity_id = null;

    public ?string $economic_activity_description = null;

    // Persona natural.
    public ?string $document_issued_on = null;

    public ?string $nationality_country_id = null;

    public ?string $birth_date = null;

    public ?string $birth_department_id = null;

    public ?string $birth_municipality_id = null;

    public ?string $education_level = null;

    public ?string $residence_zone = null;

    public ?string $protection_group = null;

    public ?string $protection_group_other = null;

    public ?string $occupation = null;

    public ?string $occupation_other = null;

    public ?string $employer_name = null;

    public ?string $employer_activity = null;

    public ?string $employer_economic_activity_id = null;

    public ?string $minor_representative_name = null;

    public ?string $minor_representative_identification = null;

    public bool $has_foreign_accounts = false;

    public bool $accepts_electronic_means = false;

    // Persona jurídica.
    public ?string $tax_regime = null;

    public ?string $company_type = null;

    public ?string $legal_representative_name = null;

    public ?string $legal_representative_document_type = null;

    public ?string $legal_representative_identification = null;

    public ?string $total_assets = null;

    public ?string $total_liabilities = null;

    public ?string $total_equity = null;

    public bool $in_insolvency = false;

    public bool $accepts_risk_bureau = false;

    // Información financiera y moneda extranjera.
    public ?string $monthly_income = null;

    public ?string $other_income = null;

    public ?string $other_income_activity = null;

    public bool $foreign_currency_operations = false;

    public ?string $foreign_currency_detail = null;

    // PEP.
    public bool $manages_public_resources = false;

    public bool $declares_pep = false;

    public ?string $public_office = null;

    public ?string $pep_starts_on = null;

    public ?string $pep_ends_on = null;

    public bool $has_pep_relationship = false;

    // Contactos y declaraciones.
    /** @var array<string, array{name: ?string, phone: ?string, email: ?string}> */
    public array $contacts = [];

    public ?string $dispatch_schedule = null;

    public bool $accepts_truthfulness = false;

    public bool $accepts_data_processing = false;

    public bool $accepts_information_received = false;

    public bool $accepts_funds_origin = false;

    public function setVersion(ProviderVersion $version): void
    {
        $this->version = $version;
        $this->identityLocked = $version->provider->isIdentityLocked();

        // Los valores se llevan al formato de los campos: enums y números como texto, fechas Y-m-d.
        $values = [];
        foreach (ProviderVersion::FORM_FIELDS as $field) {
            $value = $version->getAttribute($field);

            $values[$field] = match (true) {
                is_bool($value) => $value,
                $value instanceof \BackedEnum => (string) $value->value,
                $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                $value === null => is_bool($this->{$field}) ? false : null,
                default => (string) $value,
            };
        }
        $this->fill($values);

        $this->contacts = self::emptyContacts();
        foreach ($version->contacts as $contact) {
            $this->contacts[$contact->role->value] = $contact->only(['name', 'phone', 'email']);
        }
    }

    public function personType(): PersonType
    {
        return PersonType::tryFrom($this->person_type) ?? PersonType::Legal;
    }

    public function isNatural(): bool
    {
        return $this->personType() === PersonType::Natural;
    }

    /** Documento que exige nacionalidad (cédula de extranjería o pasaporte). */
    public function requiresNationality(): bool
    {
        return in_array($this->document_type, [IdentityDocumentType::ForeignerId->value, IdentityDocumentType::Passport->value], true);
    }

    /** Documento de menor de edad: exige representante legal o apoderado. */
    public function isMinor(): bool
    {
        return in_array($this->document_type, [IdentityDocumentType::CivilRegistry->value, IdentityDocumentType::IdentityCard->value], true);
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $natural = $this->isNatural();
        $requiredNatural = $natural ? 'required' : 'nullable';
        $requiredLegal = $natural ? 'nullable' : 'required';

        return [
            'person_type' => ['required', Rule::enum(PersonType::class)],
            'document_type' => ['required', Rule::in(array_map(fn (IdentityDocumentType $type) => $type->value, $this->personType()->documentTypes()))],
            // Solo dígitos y sin DV para NIT; pasaporte y CE admiten letras. Único entre proveedores, incluidos eliminados.
            'identification' => [
                'required', 'string', 'max:20', 'regex:/^[0-9A-Za-z]+$/',
                Rule::unique(Provider::class, 'identification')->ignore($this->version?->provider_id),
            ],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'integer', GeographicLocation::existsRule(GeographicLevel::Department)],
            'municipality_id' => ['required', 'integer', $this->municipalityOf($this->department_id)],
            'country_id' => ['required', 'integer', GeographicLocation::existsRule(GeographicLevel::Country)],
            'economic_activity_id' => ['required', 'integer', Rule::exists(EconomicActivity::class, 'id')],
            'economic_activity_description' => ['nullable', 'string', 'max:255'],

            'document_issued_on' => [$requiredNatural, 'nullable', 'date', 'before_or_equal:today'],
            'nationality_country_id' => [$natural && $this->requiresNationality() ? 'required' : 'nullable', 'integer', GeographicLocation::existsRule(GeographicLevel::Country)],
            'birth_date' => [$requiredNatural, 'nullable', 'date', 'before:today'],
            'birth_department_id' => ['nullable', 'integer', GeographicLocation::existsRule(GeographicLevel::Department)],
            'birth_municipality_id' => ['nullable', 'integer', $this->municipalityOf($this->birth_department_id)],
            'education_level' => [$requiredNatural, 'nullable', Rule::in($this->options(ProviderVersion::EDUCATION_LEVEL_CHARACTERISTIC_CODE))],
            'residence_zone' => [$requiredNatural, 'nullable', Rule::enum(ResidenceZone::class)],
            'protection_group' => [$requiredNatural, 'nullable', Rule::in($this->options(ProviderVersion::PROTECTION_GROUP_CHARACTERISTIC_CODE))],
            'protection_group_other' => [$natural && $this->protection_group === self::OTHER_OPTION ? 'required' : 'nullable', 'string', 'max:150'],
            'occupation' => [$requiredNatural, 'nullable', Rule::in($this->options(ProviderVersion::OCCUPATION_CHARACTERISTIC_CODE))],
            'occupation_other' => [$natural && $this->occupation === self::OTHER_OPTION ? 'required' : 'nullable', 'string', 'max:150'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'employer_activity' => ['nullable', 'string', 'max:255'],
            'employer_economic_activity_id' => ['nullable', 'integer', Rule::exists(EconomicActivity::class, 'id')],
            'minor_representative_name' => [$natural && $this->isMinor() ? 'required' : 'nullable', 'string', 'max:255'],
            'minor_representative_identification' => [$natural && $this->isMinor() ? 'required' : 'nullable', 'string', 'max:20'],
            'has_foreign_accounts' => ['boolean'],
            'accepts_electronic_means' => ['boolean'],

            'tax_regime' => [$requiredLegal, 'nullable', Rule::in($this->options(ProviderVersion::TAX_REGIME_CHARACTERISTIC_CODE))],
            'company_type' => [$requiredLegal, 'nullable', Rule::enum(CompanyType::class)],
            'legal_representative_name' => [$requiredLegal, 'nullable', 'string', 'max:255'],
            'legal_representative_document_type' => [$requiredLegal, 'nullable', Rule::in(array_map(fn (IdentityDocumentType $type) => $type->value, IdentityDocumentType::forLegalRepresentative()))],
            'legal_representative_identification' => [$requiredLegal, 'nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-z]+$/'],
            'total_assets' => [$requiredLegal, 'nullable', 'numeric', 'min:0', 'max:9999999999999999'],
            'total_liabilities' => [$requiredLegal, 'nullable', 'numeric', 'min:0', 'max:9999999999999999'],
            'total_equity' => [$requiredLegal, 'nullable', 'numeric', 'min:-9999999999999999', 'max:9999999999999999'],
            'in_insolvency' => ['boolean'],
            'accepts_risk_bureau' => ['boolean'],

            'monthly_income' => ['required', 'numeric', 'min:0', 'max:9999999999999999'],
            'other_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999999999'],
            'other_income_activity' => [$this->other_income > 0 ? 'required' : 'nullable', 'string', 'max:255'],
            'foreign_currency_operations' => ['boolean'],
            'foreign_currency_detail' => [$this->foreign_currency_operations ? 'required' : 'nullable', 'string', 'max:255'],

            'manages_public_resources' => ['boolean'],
            'declares_pep' => ['boolean'],
            'public_office' => [$this->declares_pep ? 'required' : 'nullable', 'string', 'max:150'],
            'pep_starts_on' => [$this->declares_pep ? 'required' : 'nullable', 'date', 'before_or_equal:today'],
            'pep_ends_on' => ['nullable', 'date', 'after_or_equal:pep_starts_on'],
            'has_pep_relationship' => ['boolean'],

            'contacts' => ['array'],
            'contacts.*.name' => ['nullable', 'string', 'max:150'],
            'contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'contacts.*.email' => ['nullable', 'email:rfc', 'max:150'],
            'dispatch_schedule' => ['nullable', 'string', 'max:1000'],

            'accepts_truthfulness' => ['boolean'],
            'accepts_data_processing' => ['boolean'],
            'accepts_information_received' => ['boolean'],
            'accepts_funds_origin' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'identification.unique' => 'Ya existe un proveedor con esta identificación: vincúlelo a su proceso.',
            'identification.regex' => 'La identificación solo admite letras y números, sin puntos, guiones ni dígito de verificación.',
            'legal_representative_identification.regex' => 'La identificación solo admite letras y números.',
            'municipality_id.exists' => 'El municipio no pertenece al departamento elegido.',
            'birth_municipality_id.exists' => 'El municipio no pertenece al departamento elegido.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'person_type' => 'tipo de persona', 'document_type' => 'tipo de documento', 'identification' => 'identificación',
            'name' => $this->isNatural() ? 'nombres y apellidos' : 'razón social', 'email' => 'correo electrónico',
            'phone' => 'teléfono', 'address' => 'dirección', 'department_id' => 'departamento', 'municipality_id' => 'municipio',
            'country_id' => $this->isNatural() ? 'país de residencia' : 'país de constitución', 'economic_activity_id' => 'CIIU',
            'document_issued_on' => 'fecha de expedición', 'nationality_country_id' => 'nacionalidad', 'birth_date' => 'fecha de nacimiento',
            'education_level' => 'nivel de educación', 'residence_zone' => 'zona', 'protection_group' => 'grupo de protección',
            'protection_group_other' => 'otro grupo de protección', 'occupation' => 'ocupación', 'occupation_other' => 'otra ocupación',
            'minor_representative_name' => 'representante del menor', 'minor_representative_identification' => 'identificación del representante',
            'tax_regime' => 'régimen tributario', 'company_type' => 'tipo de empresa', 'legal_representative_name' => 'representante legal',
            'legal_representative_document_type' => 'tipo de documento del representante', 'legal_representative_identification' => 'identificación del representante',
            'total_assets' => 'total de activos', 'total_liabilities' => 'total de pasivos', 'total_equity' => 'total de patrimonio',
            'monthly_income' => 'ingresos mensuales', 'other_income' => 'otros ingresos', 'other_income_activity' => 'actividad de otros ingresos',
            'foreign_currency_detail' => 'operaciones en moneda extranjera', 'public_office' => 'cargo público',
            'pep_starts_on' => 'inicio del cargo PEP', 'pep_ends_on' => 'fin del cargo PEP', 'contacts.*.email' => 'correo del contacto',
        ];
    }

    /**
     * Datos validados y limpios: los campos del formulario y los contactos por separado.
     *
     * @return array{data: array<string, mixed>, contacts: array<string, array{name: ?string, phone: ?string, email: ?string}>}
     */
    public function payload(): array
    {
        $validated = $this->sanitize($this->validate());
        $contacts = $validated['contacts'] ?? [];
        unset($validated['contacts']);

        return ['data' => $validated, 'contacts' => $contacts];
    }

    /**
     * Un contacto vacío por cada cargo fijo del formato.
     *
     * @return array<string, array{name: ?string, phone: ?string, email: ?string}>
     */
    public static function emptyContacts(): array
    {
        return collect(ProviderContactRole::cases())
            ->mapWithKeys(fn (ProviderContactRole $role) => [$role->value => ['name' => null, 'phone' => null, 'email' => null]])
            ->all();
    }

    /** @return list<string> */
    private function options(string $characteristicCode): array
    {
        return array_values(CharacteristicOption::forCharacteristicCode($characteristicCode)->keys()->map(fn ($name) => (string) $name)->values()->all());
    }

    /** El municipio debe pertenecer al departamento elegido. */
    private function municipalityOf(?string $departmentId): Exists
    {
        return GeographicLocation::existsRule(GeographicLevel::City, $departmentId, withParent: true);
    }
}
