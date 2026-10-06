<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Enums\UserStatus;
use App\Livewire\Concerns\SanitizesPayload;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Validate;
use Livewire\Form;

class UserForm extends Form
{
    use SanitizesPayload;

    public ?User $user = null;

    public string $identification = '';

    public string $name = '';

    public string $lastname = '';

    public string $area = '';

    public string $email = '';

    public string $status = 'active';

    public ?string $role_id = null;

    public string $service_point_id = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $profile_photo_path = '';

    /**
     * Summary of rules
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        // Normaliza antes de validar: un <select> vacío llega como '', no como null
        $this->role_id = $this->role_id === '' ? null : $this->role_id;

        return [
            'identification' => ['required', 'string','min:7', 'max:12',
                                'regex:/^\\d{7,12}$/',
                                Rule::unique('users', 'identification')->ignore($this->user)],
            'name'     => ['required', 'string', 'min:3', 'max:150'],
            'lastname' => ['required', 'string', 'min:3', 'max:150'],
            'area'     => ['nullable', 'string', 'in:Administrativa,Comercial,Farmacia,Financiero'],
            'email'    => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->user)],
            'status'   => ['required', Rule::enum(UserStatus::class)],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'service_point_id'  => ['nullable', 'integer', Rule::exists('service_points', 'id')],
            'profile_photo_path'  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function setUser(User $user): void
    {
        $this->user = $user;

        foreach (['identification', 'name', 'lastname', 'area', 'role_id', 'email', 'service_point_id'] as $field) {
            $this->{$field} = (string) ($user->{$field} ?? '');
        }

        $this->status = $user->status?->value ?? 'active';
    }

    /** @return array<string, mixed> Solo datos validados; sin password si viene vacío */
    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }
}
