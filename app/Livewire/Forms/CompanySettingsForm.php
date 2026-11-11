<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\ManagementApprovalMode;
use App\Enums\ProviderScope;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\User;
use App\Settings\CompanySettings;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Livewire\Form;
use Modules\HumanResources\App\Models\HrCharacteristic;
use Modules\HumanResources\App\Models\HrCharacteristicOption;

/**
 * Formulario de los datos de la empresa (configuración global).
 * El logo se valida y guarda aparte, en el componente y en UpdateCompanySettings.
 */
final class CompanySettingsForm extends Form
{
    use SanitizesPayload;

    public string $legal_name = '';

    public string $nit = '';

    public ?string $address = null;

    public ?string $city = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $website = null;

    // Oficial de cumplimiento (revisa a los proveedores PEP).
    public ?int $compliance_officer_id = null;

    /**
     * SMMLV por año, como filas editables.
     *
     * @var list<array{year: int|string, amount: int|string}>
     */
    public array $minimum_wages = [];

    // Múltiplo del SMMLV desde el cual se exige el FO-300-01 a un proveedor.
    public int|string $provider_form_wage_multiplier = 2;

    // Cómo aprueba la Gerencia a los proveedores PEP (ManagementApprovalMode).
    public string $provider_pep_management_approval = 'physical';

    /** @var list<string> Cargos que verifican proveedores administrativos (vacío = cualquiera con permiso). */
    public array $administrative_verifier_positions = [];

    /** @var list<string> Cargos que verifican proveedores comerciales (vacío = cualquiera con permiso). */
    public array $commercial_verifier_positions = [];

    public function setSettings(CompanySettings $settings): void
    {
        $this->fill([
            'legal_name' => $settings->legal_name,
            'nit' => $settings->nit,
            'address' => $settings->address,
            'city' => $settings->city,
            'phone' => $settings->phone,
            'email' => $settings->email,
            'website' => $settings->website,
            'compliance_officer_id' => $settings->compliance_officer_id,
            'provider_form_wage_multiplier' => $settings->provider_form_wage_multiplier,
            'provider_pep_management_approval' => $settings->managementApprovalMode()->value,
            'administrative_verifier_positions' => array_map('strval', $settings->verifierPositions(ProviderScope::Administrative)),
            'commercial_verifier_positions' => array_map('strval', $settings->verifierPositions(ProviderScope::Commercial)),
        ]);

        $wages = $settings->minimum_wages;
        krsort($wages);
        $this->minimum_wages = array_map(
            fn (string|int $year, int $amount) => ['year' => (int) $year, 'amount' => $amount],
            array_keys($wages), $wages,
        );
    }

    /** Agrega una fila para el SMMLV de un nuevo año. */
    public function addMinimumWage(): void
    {
        $years = array_map(fn (array $row) => (int) $row['year'], $this->minimum_wages);
        array_unshift($this->minimum_wages, ['year' => $years === [] ? (int) date('Y') : max($years) + 1, 'amount' => '']);
    }

    public function removeMinimumWage(int $index): void
    {
        $this->minimum_wages = array_values(array_filter(
            $this->minimum_wages,
            fn (int $key) => $key !== $index,
            ARRAY_FILTER_USE_KEY,
        ));
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'min:3', 'max:150'],
            // Solo dígitos, con dígito de verificación opcional separado por guion (ej. 812001561-1).
            'nit' => ['required', 'string', 'regex:/^\d{6,12}(-\d)?$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'compliance_officer_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')->whereNull('deleted_at')],
            'minimum_wages' => ['array', 'min:1'],
            'minimum_wages.*.year' => ['required', 'integer', 'between:2000,2100', 'distinct'],
            'minimum_wages.*.amount' => ['required', 'integer', 'min:1', 'max:999999999'],
            'provider_form_wage_multiplier' => ['required', 'integer', 'min:1', 'max:100'],
            'provider_pep_management_approval' => ['required', Rule::enum(ManagementApprovalMode::class)],
            'administrative_verifier_positions' => ['array'],
            'administrative_verifier_positions.*' => ['integer', $this->positionRule()],
            'commercial_verifier_positions' => ['array'],
            'commercial_verifier_positions.*' => ['integer', $this->positionRule()],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'nit.regex' => 'El NIT debe tener solo dígitos y, si aplica, el dígito de verificación separado por guion (ej. 812001561-1).',
            'minimum_wages.min' => 'Registre al menos el SMMLV del año en curso.',
            'minimum_wages.*.year.distinct' => 'El año está repetido.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'minimum_wages.*.year' => 'año', 'minimum_wages.*.amount' => 'SMMLV',
            'provider_form_wage_multiplier' => 'múltiplo del SMMLV',
            'provider_pep_management_approval' => 'aprobación de Gerencia',
            'administrative_verifier_positions.*' => 'cargo verificador', 'commercial_verifier_positions.*' => 'cargo verificador',
        ];
    }

    /**
     * Datos validados con el SMMLV como mapa año => valor, como lo guarda CompanySettings.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->sanitize($this->validate());

        $wages = [];
        foreach ($this->minimum_wages as $row) {
            $wages[(string) $row['year']] = (int) $row['amount'];
        }
        ksort($wages);

        $data['minimum_wages'] = $wages;
        $data['provider_form_wage_multiplier'] = (int) $data['provider_form_wage_multiplier'];
        $data['provider_verifier_positions'] = [
            ProviderScope::Administrative->value => array_values(array_unique(array_map('intval', $this->administrative_verifier_positions))),
            ProviderScope::Commercial->value => array_values(array_unique(array_map('intval', $this->commercial_verifier_positions))),
        ];
        unset($data['administrative_verifier_positions'], $data['commercial_verifier_positions']);

        return $data;
    }

    /** Cargo existente de la característica Cargos de Talento Humano. */
    private function positionRule(): Exists
    {
        return Rule::exists(HrCharacteristicOption::class, 'id')->whereNull('deleted_at')
            ->whereIn('hr_characteristic_id', HrCharacteristic::query()->where('code', HrCharacteristic::POSITIONS_CODE)->pluck('id')->all());
    }
}
