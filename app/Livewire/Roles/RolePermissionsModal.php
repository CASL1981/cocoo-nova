<?php

namespace App\Livewire\Roles;

use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class RolePermissionsModal extends Component
{
    use ManagesModal, Notifies;

    public ?Role $role = null;
    public array $matrix = [];   // ['users' => ['read' => true, 'create' => false, ...], ...]

    public bool $readOnly = false;

    #[On('open-role-permissions')]
    public function openEditable(int $roleId): void
    {
        $role = Role::findOrFail($roleId);
        Gate::authorize('managePermissions', $role);

        $this->loadAndOpen($role, readOnly: false);
    }

    #[On('view-role-permissions')]
    public function openReadOnly(int $roleId): void
    {
        $role = Role::findOrFail($roleId);
        Gate::authorize('view', $role);

        $this->loadAndOpen($role, readOnly: true);
    }

    private function loadAndOpen(Role $role, bool $readOnly): void
    {
        $this->openModal();          // resetea todo primero (role, matrix, readOnly → false)

        $this->role = $role;
        $this->readOnly = $readOnly; // ← se asigna DESPUÉS del reset, ya no se pierde

        $assigned = $role->permissions->pluck('name')->all();

        $this->matrix = [];
        foreach (PermissionCatalog::MODULES as $module => $label) {
            foreach (array_keys(PermissionCatalog::ACTIONS) as $action) {
                $this->matrix[$module][$action] = in_array(
                    PermissionCatalog::permissionName($module, $action),
                    $assigned,
                    true
                );
            }
        }
    }

    protected function resetModalState(): void
    {
        $this->role = null;
        $this->matrix = [];
        $this->readOnly = false;
    }

    public function save(): void
    {
        // Defensa adicional: aunque el botón esté oculto en modo lectura, el método no debe ejecutar nada
        if ($this->readOnly) {
            return;
        }

        Gate::authorize('managePermissions', $this->role);

        $selected = [];
        foreach ($this->matrix as $module => $actions) {
            foreach ($actions as $action => $checked) {
                if ($checked) {
                    $selected[] = PermissionCatalog::permissionName($module, $action);
                }
            }
        }

        $this->role->syncPermissions($selected);

        $this->dispatch('role-permissions-saved');
        $this->toastSuccess("Permisos actualizados para el rol {$this->role->name}");
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.roles.role-permissions-modal', [
            'modules' => PermissionCatalog::MODULES,
            'actions' => PermissionCatalog::ACTIONS,
            'actionIcons' => PermissionCatalog::ACTION_ICONS,
            'moduleIcons' => PermissionCatalog::MODULE_ICONS,
        ]);
    }
}
