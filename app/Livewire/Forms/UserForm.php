<?php

namespace App\Livewire\Forms;

use App\Enums\UserStatus;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;
use Modules\HumanResources\App\Models\HrCharacteristic;
use Modules\HumanResources\App\Models\HrCharacteristicOption;

class UserForm extends Form
{
    use SanitizesPayload;

    public ?User $user = null;

    public string $identification = '';

    public string $name = '';

    public string $lastname = '';

    public string $process_id = '';

    // Cargo (Talento Humano → Cargos): se muestra en las firmas y define quién verifica proveedores.
    public string $position_id = '';

    // Habilita al usuario para hacer requisiciones de aseo, papelería y cafetería ('1' o '0').
    public string $can_request_supplies = '0';

    public string $email = '';

    public string $status = 'active';

    public ?string $role_id = null;

    public string $service_point_id = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $profile_photo_path = '';

    /**
     * Summary of rules
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        // Normaliza antes de validar: un <select> vacío llega como '', no como null
        $this->role_id = $this->role_id === '' ? null : $this->role_id;

        return [
            'identification' => ['required', 'string', 'min:7', 'max:12',
                'regex:/^\\d{7,12}$/',
                Rule::unique('users', 'identification')->ignore($this->user)],
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'lastname' => ['required', 'string', 'min:3', 'max:150'],
            // Quien solicita suministros necesita proceso (define quién aprueba) y sede (su C.U.).
            'process_id' => ['nullable', 'required_if:can_request_supplies,1', 'integer',
                Rule::exists('processes', 'id')->whereNull('deleted_at')],
            'position_id' => ['nullable', 'integer', Rule::exists(HrCharacteristicOption::class, 'id')->whereNull('deleted_at')
                ->whereIn('hr_characteristic_id', HrCharacteristic::query()->where('code', HrCharacteristic::POSITIONS_CODE)->pluck('id')->all())],
            'can_request_supplies' => ['required', 'boolean'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->user)],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'service_point_id' => ['nullable', 'required_if:can_request_supplies,1', 'integer', Rule::exists('service_points', 'id')],
            'profile_photo_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function setUser(User $user): void
    {
        $this->user = $user;

        foreach (['identification', 'name', 'lastname', 'process_id', 'position_id', 'role_id', 'email', 'service_point_id'] as $field) {
            $this->{$field} = (string) ($user->{$field} ?? '');
        }

        $this->status = $user->status->value;
        $this->can_request_supplies = $user->can_request_supplies ? '1' : '0';
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'process_id.required_if' => 'El proceso es obligatorio para solicitar suministros.',
            'service_point_id.required_if' => 'La sede es obligatoria para solicitar suministros.',
        ];
    }

    /** @return array<string, mixed> Solo datos validados; sin password si viene vacío */
    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }
}
