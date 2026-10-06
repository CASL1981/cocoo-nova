<?php

namespace App\Livewire\Forms;

use App\Enums\ServicePointStatus;
use App\Enums\ServicePointType;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\ServicePoint;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Datos y validación del formulario de ServicePoint (modal de crear/editar).
 */
class ServicePointForm extends Form
{
    use SanitizesPayload;

    public ?ServicePoint $servicePoint = null;

    public string $code = '';
    public string $name = '';
    public string $type = 'other';
    public string $address = '';
    public string $phone = '';
    public string $minimum = '';
    public string $maximum = '';
    public string $status = 'active';

    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('service_points', 'code')->ignore($this->servicePoint)],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(ServicePointType::class)],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'minimum' => ['nullable', 'integer', 'min:0'],
            'maximum' => ['nullable', 'integer', 'min:0', 'gte:minimum'],
            'status' => ['required', Rule::enum(ServicePointStatus::class)],
        ];
    }

    public function setServicePoint(ServicePoint $servicePoint): void
    {
        $this->servicePoint = $servicePoint;
        $this->code = $servicePoint->code;
        $this->name = $servicePoint->name;
        $this->type = $servicePoint->type->value;
        $this->address = (string) ($servicePoint->address ?? '');
        $this->phone = (string) ($servicePoint->phone ?? '');
        $this->minimum = (string) ($servicePoint->minimum ?? '');
        $this->maximum = (string) ($servicePoint->maximum ?? '');
        $this->status = $servicePoint->status->value;
    }

    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }
}
