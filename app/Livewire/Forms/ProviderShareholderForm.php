<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\IdentityDocumentType;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\ProviderVersionShareholder;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Captura en línea de un accionista, propietario o consorciado (persona jurídica).
 *
 * Un accionista con NIT es una persona jurídica: su representante legal (nombre y cédula) es
 * obligatorio. Con cualquier otro documento es una persona natural y no lleva representante.
 */
final class ProviderShareholderForm extends Form
{
    use SanitizesPayload;

    public string $document_type = 'CC';

    public string $identification = '';

    public string $name = '';

    public ?string $legal_representative_name = null;

    public ?string $legal_representative_identification = null;

    public string $ownership_percentage = '';

    /** El accionista es una persona jurídica (NIT) y debe registrar su representante legal. */
    public function requiresLegalRepresentative(): bool
    {
        return $this->document_type === IdentityDocumentType::Nit->value;
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $representative = $this->requiresLegalRepresentative() ? 'required' : 'prohibited';

        return [
            'document_type' => ['required', Rule::in(array_map(fn (IdentityDocumentType $type) => $type->value, IdentityDocumentType::forShareholders()))],
            'identification' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z]+$/'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'legal_representative_name' => [$representative, 'nullable', 'string', 'min:3', 'max:255'],
            'legal_representative_identification' => [$representative, 'nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-z]+$/'],
            // Solo se registran participaciones iguales o mayores al 5 % (nota del formato).
            'ownership_percentage' => ['required', 'numeric', 'min:'.ProviderVersionShareholder::MIN_OWNERSHIP_PERCENTAGE, 'max:100'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'legal_representative_name.required' => 'El accionista con NIT es una persona jurídica: ingrese el nombre de su representante legal.',
            'legal_representative_identification.required' => 'El accionista con NIT es una persona jurídica: ingrese la cédula de su representante legal.',
            'legal_representative_name.prohibited' => 'Solo un accionista con NIT (persona jurídica) lleva representante legal.',
            'legal_representative_identification.prohibited' => 'Solo un accionista con NIT (persona jurídica) lleva representante legal.',
            'legal_representative_identification.regex' => 'La identificación solo admite letras y números.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'document_type' => 'tipo de documento', 'identification' => 'identificación', 'name' => 'nombre o razón social',
            'legal_representative_name' => 'representante legal', 'legal_representative_identification' => 'cédula del representante legal',
            'ownership_percentage' => 'participación',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }
}
