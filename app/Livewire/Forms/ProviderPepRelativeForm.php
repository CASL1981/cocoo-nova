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
 * Captura en línea de un familiar o persona relacionada con un PEP.
 */
final class ProviderPepRelativeForm extends Form
{
    use SanitizesPayload;

    public ?string $document_type = 'CC';

    public ?string $identification = null;

    public string $name = '';

    public string $relationship = '';

    public bool $is_pep = false;

    public ?string $public_office = null;

    public ?string $pep_starts_on = null;

    public ?string $pep_ends_on = null;

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'document_type' => ['nullable', Rule::in(array_map(fn (IdentityDocumentType $type) => $type->value, IdentityDocumentType::forPeople()))],
            'identification' => ['nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-z]+$/'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'relationship' => ['required', Rule::in(CharacteristicOption::forCharacteristicCode(ProviderVersion::RELATIONSHIP_CHARACTERISTIC_CODE)->keys()->all())],
            'is_pep' => ['boolean'],
            'public_office' => [$this->is_pep ? 'required' : 'nullable', 'string', 'max:150'],
            'pep_starts_on' => ['nullable', 'date', 'before_or_equal:today'],
            'pep_ends_on' => ['nullable', 'date', 'after_or_equal:pep_starts_on'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'document_type' => 'tipo de documento', 'identification' => 'identificación', 'name' => 'nombre',
            'relationship' => 'parentesco', 'public_office' => 'cargo PEP', 'pep_starts_on' => 'inicio del cargo', 'pep_ends_on' => 'fin del cargo',
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }
}
