<?php

namespace App\Livewire\Roles;

use App\Actions\Permissions\SeedModulePermissions;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class RolePermissionsModal extends Component
{
    use ManagesModal, Notifies;

    public ?Role $role = null;

    /**
     * Permisos marcados por módulo y acción (ej. ['users' => ['read' => true, 'create' => false]]).
     *
     * @var array<string, array<string, bool>>
     */
    public array $matrix = [];

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

    public function save(SeedModulePermissions $seedPermissions): void
    {
        // Defensa adicional: aunque el botón esté oculto en modo lectura, el método no debe ejecutar nada
        if ($this->readOnly) {
            return;
        }

        Gate::authorize('managePermissions', $this->role);

        // Solo se aceptan módulos y acciones del catálogo: la matriz llega del cliente.
        $selected = [];
        foreach (array_keys(PermissionCatalog::MODULES) as $module) {
            foreach (array_keys(PermissionCatalog::ACTIONS) as $action) {
                if (! empty($this->matrix[$module][$action])) {
                    $selected[PermissionCatalog::permissionName($module, $action)] = $module;
                }
            }
        }

        // Un módulo agregado al catálogo después de sembrar la base aún no tiene permisos:
        // se crean aquí para no fallar con PermissionDoesNotExist.
        $existing = Permission::whereIn('name', array_keys($selected))->where('guard_name', 'web')->pluck('name')->all();
        foreach (array_unique(array_diff_key($selected, array_flip($existing))) as $module) {
            $seedPermissions->execute($module);
        }

        $this->role->syncPermissions(array_keys($selected));

        $this->dispatch('role-permissions-saved');
        $this->toastSuccess("Permisos actualizados para el rol {$this->role->name}");
        $this->closeModal();
    }

    public function render(): View
    {
        return view('livewire.roles.role-permissions-modal', [
            'modules' => PermissionCatalog::MODULES,
            'actions' => PermissionCatalog::ACTIONS,
            'actionIcons' => PermissionCatalog::ACTION_ICONS,
            'moduleIcons' => PermissionCatalog::MODULE_ICONS,
        ]);
    }
}
