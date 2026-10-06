<?php

namespace App\Livewire\Forms;

use App\Livewire\Concerns\SanitizesPayload;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Livewire\Form;

class RoleForm extends Form
{
    use SanitizesPayload;

    public ?Role $role = null;

    public string $name = '';

    public string $guard_name = 'web';

    protected function rules(): array
    {
        return [
            'name' => [
            'required', 'string', 'max:100',
            Rule::unique('roles', 'name')
                ->where(fn ($q) => $q->where('guard_name', $this->guard_name))
                ->ignore($this->role),
            ],
        ];
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
        $this->name = $role->name;
        $this->guard_name = $role->guard_name;
    }

    public function payload(): array
    {
        return $this->sanitize([...$this->validate(), 'guard_name' => $this->guard_name]);
    }
}
