<?php

namespace App\Livewire\Roles;

use App\Actions\Roles\SaveRole;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\RoleForm;
use App\Models\Role;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class RoleFormModal extends Component
{
    use ManagesModal, Notifies;

    public RoleForm $form;

    #[On('open-role-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', Role::class);
        $this->openModal();
    }

    #[On('open-role-edit')]
    public function openEdit(int $roleId): void
    {
        $role = Role::findOrFail($roleId);
        Gate::authorize('update', $role);

        $this->openModal();
        $this->form->setRole($role);
    }

    #[On('role-saved')]
    #[On('user-saved')]
    public function refreshRoleCards(): void
    {
        unset($this->topRoles, $this->roles);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    public function save(SaveRole $saveRole): void
    {
        $role = $this->form->role;
        $role ? Gate::authorize('update', $role) : Gate::authorize('create', Role::class);

        try {
            $saveRole->execute($this->form->payload(), $role);
        } catch (QueryException $e) {
            Log::error('roles.save_failed', ['role_id' => $role?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar el rol. Inténtalo de nuevo.');
            return;
        }

        $this->dispatch('role-saved');
        $this->toastSuccess($role ? 'Rol actualizado' : 'Rol creado exitosamente');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.roles.role-form-modal');
    }
}
