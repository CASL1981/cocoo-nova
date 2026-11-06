<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\GeographicLevel;
use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\CharacteristicOption;
use App\Models\GeographicLocation;
use App\Models\Provider;
use App\Models\ProviderLinkage;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Registro básico de un proveedor sin FO-300-01 (compras menores a 2 SMMLV): identificación,
 * contacto y ubicación, más la condición y el plazo de pago del proceso.
 */
final class ProviderBasicForm extends Form
{
    use SanitizesPayload;

    // Proveedor en edición; null al registrar uno nuevo.
    public ?Provider $provider = null;

    // Vinculación del proceso cuyas condiciones de pago se editan.
    public ?ProviderLinkage $linkage = null;

    // Identificación bloqueada: el proveedor ya está en uso o tiene formato aprobado.
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

    public ?string $contact_name = null;

    public ?string $payment_condition = null;

    public ?string $payment_term = null;

    public function setProvider(Provider $provider, ProviderLinkage $linkage): void
    {
        $provider->loadMissing('municipality');
        $this->provider = $provider;
        $this->linkage = $linkage;
        $this->identityLocked = $provider->isIdentityLocked();
        $this->fill([
            'person_type' => $provider->person_type->value,
            'document_type' => $provider->document_type->value,
            'identification' => $provider->identification,
            'name' => $provider->name,
            'email' => $provider->email,
            'phone' => $provider->phone,
            'address' => $provider->address,
            'department_id' => $provider->municipality ? (string) $provider->municipality->parent_id : null,
            'municipality_id' => $provider->municipality_id ? (string) $provider->municipality_id : null,
            'contact_name' => $provider->contact_name,
            'payment_condition' => $linkage->payment_condition,
            'payment_term' => $linkage->payment_term,
        ]);
    }

    public function personType(): PersonType
    {
        return PersonType::tryFrom($this->person_type) ?? PersonType::Legal;
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'person_type' => ['required', Rule::enum(PersonType::class)],
            'document_type' => ['required', Rule::in(array_map(fn (IdentityDocumentType $type) => $type->value, $this->personType()->documentTypes()))],
            'identification' => [
                'required', 'string', 'max:20', 'regex:/^[0-9A-Za-z]+$/',
                Rule::unique(Provider::class, 'identification')->ignore($this->provider?->id),
            ],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'integer', GeographicLocation::existsRule(GeographicLevel::Department)],
            'municipality_id' => ['required', 'integer', GeographicLocation::existsRule(GeographicLevel::City, $this->department_id, withParent: true)],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'payment_condition' => ['nullable', Rule::in($this->options(ProviderLinkage::PAYMENT_CONDITION_CHARACTERISTIC_CODE))],
            'payment_term' => ['nullable', Rule::in($this->options(ProviderLinkage::PAYMENT_TERM_CHARACTERISTIC_CODE))],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'identification.unique' => 'Ya existe un proveedor con esta identificación: vincúlelo a su proceso.',
            'identification.regex' => 'La identificación solo admite letras y números, sin puntos, guiones ni dígito de verificación.',
            'municipality_id.exists' => 'El municipio no pertenece al departamento elegido.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'person_type' => 'tipo de persona', 'document_type' => 'tipo de documento', 'identification' => 'identificación',
            'name' => 'nombre o razón social', 'email' => 'correo electrónico', 'phone' => 'teléfono', 'address' => 'dirección',
            'department_id' => 'departamento', 'municipality_id' => 'municipio', 'contact_name' => 'contacto',
            'payment_condition' => 'condición de pago', 'payment_term' => 'plazo de pago',
        ];
    }

    /**
     * Datos del proveedor y condiciones de la vinculación, validados y por separado.
     *
     * @return array{provider: array<string, mixed>, terms: array{payment_condition: ?string, payment_term: ?string}}
     */
    public function payload(): array
    {
        $data = $this->sanitize($this->validate());
        $terms = ['payment_condition' => $data['payment_condition'] ?? null, 'payment_term' => $data['payment_term'] ?? null];
        unset($data['payment_condition'], $data['payment_term'], $data['department_id']);

        return ['provider' => $data, 'terms' => $terms];
    }

    /** @return list<string> */
    private function options(string $code): array
    {
        return array_values(CharacteristicOption::forCharacteristicCode($code)->keys()->map(fn ($name) => (string) $name)->values()->all());
    }
}
