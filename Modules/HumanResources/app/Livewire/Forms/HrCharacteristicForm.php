<?php

declare(strict_types=1);

namespace Modules\HumanResources\App\Livewire\Forms;

use App\Enums\Status;
use App\Livewire\Concerns\SanitizesPayload;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Modules\HumanResources\App\Models\HrCharacteristic;

/**
 * Formulario de una característica de Talento Humano.
 */
final class HrCharacteristicForm extends Form
{
    use SanitizesPayload;

    public ?HrCharacteristic $characteristic = null;

    public string $name = '';

    public string $code = '';

    public string $status = 'active';

    // El código ya está en uso (la característica tiene opciones): se muestra de solo lectura.
    public bool $codeLocked = false;

    public function setCharacteristic(HrCharacteristic $characteristic): void
    {
        $this->characteristic = $characteristic;
        $this->codeLocked = $characteristic->isCodeLocked();
        $this->name = $characteristic->name;
        $this->code = $characteristic->code;
        $this->status = $characteristic->status->value;
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $code = ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', Rule::unique(HrCharacteristic::class, 'code')->ignore($this->characteristic)];

        if ($this->codeLocked && $this->characteristic !== null) {
            $code[] = Rule::in([$this->characteristic->code]);
        }

        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => $code,
            'status' => ['required', Rule::enum(Status::class)],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'code.in' => 'El código no se puede cambiar: la característica ya tiene opciones en uso.',
            'code.regex' => 'El código solo admite minúsculas, números y guion bajo (snake_case).',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return ['name' => 'nombre', 'code' => 'código', 'status' => 'estado'];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }
}
