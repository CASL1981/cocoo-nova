<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\IdentityDocumentType;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\CharacteristicOption;
use App\Models\ProviderVersion;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Captura en línea de un familiar PEP del grupo básico familiar: tipo y número de documento,
 * nombres y apellidos y parentesco (todos obligatorios, como el formato).
 */
final class ProviderPepRelativeForm extends Form
{
    use SanitizesPayload;

    public ?string $document_type = 'CC';

    public ?string $identification = null;

    public string $name = '';

    public string $relationship = '';

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(array_map(fn (IdentityDocumentType $type) => $type->value, IdentityDocumentType::forPeople()))],
            'identification' => ['required', 'string', 'max:20', 'regex:/^[0-9A-Za-z]+$/'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'relationship' => ['required', Rule::in(CharacteristicOption::forCharacteristicCode(ProviderVersion::RELATIONSHIP_CHARACTERISTIC_CODE)->keys()->all())],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'document_type' => 'tipo de documento', 'identification' => 'número de documento', 'name' => 'nombres y apellidos',
            'relationship' => 'parentesco',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }
}
