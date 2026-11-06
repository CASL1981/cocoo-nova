<?php

namespace App\Livewire\Forms;

use App\Enums\Status;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\CharacteristicOption;
use Illuminate\Validation\Rule;
use Livewire\Form;

class CharacteristicOptionForm extends Form
{
    use SanitizesPayload;

    public ?CharacteristicOption $option = null;

    public ?int $characteristicId = null;   // se fija desde afuera, no lo edita el usuario en el modal

    public string $name = '';

    public string $status = 'active';

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('characteristic_options', 'name')
                    ->where(fn ($q) => $q->where('characteristic_id', $this->characteristicId))
                    ->ignore($this->option),
            ],
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }

    public function setOption(CharacteristicOption $option): void
    {
        $this->option = $option;
        $this->characteristicId = $option->characteristic_id;
        $this->name = $option->name;
        $this->status = $option->status->value;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [...$this->sanitize($this->validate()), 'characteristic_id' => $this->characteristicId];
    }
}
