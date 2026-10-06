<?php

use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Página a la que EnsurePasswordIsNotTemporary redirige a cualquier usuario
 * con clave temporal. Al guardar, limpia must_change_password y lo libera
 * hacia el resto de la aplicación.
 */
new #[Layout('layouts.auth')] #[Title('Actualiza tu contraseña')] class extends Component
{
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $user = auth()->user();

        // forceFill(): 'must_change_password' no es mass-assignable a propósito;
        // este es el único lugar legítimo del sistema donde debe apagarse.
        $user->forceFill([
            'password' => bcrypt($data['password']),
            'must_change_password' => false,
        ])->save();

        session()->flash('success', 'Tu contraseña fue actualizada correctamente.');
        $this->redirectRoute('dashboard', navigate: true);
    }
};
?>

<div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4">
            <h4 class="mb-3">Actualiza tu contraseña</h4>
            <p class="text-muted small">Por seguridad, debes definir una contraseña propia antes de continuar.</p>

            <form wire:submit="save">
                <div class="mb-3">
                    <x-forms.input name="password" label="Nueva contraseña" type="password" autocomplete="new-password" />
                </div>
                <div class="mb-3">
                    <x-forms.input name="password_confirmation" label="Confirmar contraseña" type="password" autocomplete="new-password" />
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    Guardar y continuar
                    <span wire:loading class="spinner-border spinner-border-sm ms-1"></span>
                </button>
            </form>
        </div>
    </div>
</div>
