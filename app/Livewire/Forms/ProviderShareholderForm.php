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

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(array_map(fn (IdentityDocumentType $type) => $type->value, IdentityDocumentType::forShareholders()))],
            'identification' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z]+$/'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'legal_representative_name' => ['nullable', 'string', 'max:255'],
            'legal_representative_identification' => ['nullable', 'string', 'max:20'],
            // Solo se registran participaciones iguales o mayores al 5 % (nota del formato).
            'ownership_percentage' => ['required', 'numeric', 'min:'.ProviderVersionShareholder::MIN_OWNERSHIP_PERCENTAGE, 'max:100'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'document_type' => 'tipo de documento', 'identification' => 'identificación', 'name' => 'nombre o razón social',
            'legal_representative_name' => 'representante legal', 'legal_representative_identification' => 'identificación del representante',
            'ownership_percentage' => 'participación',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }
}
