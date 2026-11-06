<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Livewire\Concerns\SanitizesPayload;
use App\Models\User;
use App\Settings\CompanySettings;
use Illuminate\Validation\Rule;
use Livewire\Form;

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

        return $data;
    }
}
