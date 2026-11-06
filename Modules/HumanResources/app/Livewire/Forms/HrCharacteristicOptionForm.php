<?php

declare(strict_types=1);

namespace Modules\HumanResources\App\Livewire\Forms;

use App\Enums\Status;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\Process;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Modules\HumanResources\App\Models\HrCharacteristicOption;

/**
 * Formulario de una opción de Talento Humano. Proceso y jefe inmediato solo se piden en Cargos.
 */
final class HrCharacteristicOptionForm extends Form
{
    use SanitizesPayload;

    public ?HrCharacteristicOption $option = null;

    // Característica de la opción: la fija el modal (selección del panel izquierdo), no el usuario.
    public ?int $characteristicId = null;

    // La característica es Cargos: se piden proceso y jefe inmediato.
    public bool $isPositions = false;

    public string $name = '';

    public ?string $process_id = null;

    public ?string $parent_id = null;

    public string $status = 'active';

    public function setOption(HrCharacteristicOption $option): void
    {
        $this->option = $option;
        $this->characteristicId = $option->hr_characteristic_id;
        $this->name = $option->name;
        $this->process_id = $option->process_id === null ? null : (string) $option->process_id;
        $this->parent_id = $option->parent_id === null ? null : (string) $option->parent_id;
        $this->status = $option->status->value;
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $rules = [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique(HrCharacteristicOption::class, 'name')
                    ->where(fn ($query) => $query->where('hr_characteristic_id', $this->characteristicId))
                    ->ignore($this->option),
            ],
            'status' => ['required', Rule::enum(Status::class)],
        ];

        if ($this->isPositions) {
            $rules['process_id'] = ['required', 'integer', Rule::exists(Process::class, 'id')->whereNull('deleted_at')];
            $rules['parent_id'] = ['nullable', 'integer', Rule::exists(HrCharacteristicOption::class, 'id')
                ->where('hr_characteristic_id', $this->characteristicId)->whereNull('deleted_at')];
        }

        return $rules;
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return ['name.unique' => 'Ya existe una opción con este nombre en la característica (puede estar eliminada).'];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return ['name' => 'nombre', 'status' => 'estado', 'process_id' => 'proceso', 'parent_id' => 'jefe inmediato'];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [...$this->sanitize($this->validate()), 'hr_characteristic_id' => $this->characteristicId];
    }
}
