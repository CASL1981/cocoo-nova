<?php

namespace App\Livewire\Users;

use App\Actions\SaveUser;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\UserForm;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class UserFormModal extends Component
{
    use ManagesModal, Notifies;

    public UserForm $form;

    #[On('open-user-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', User::class);
        $this->openModal();
    }

    #[On('open-user-edit')]
    public function openEdit(int $userId): void
    {
        $user = User::findOrFail($userId);
        Gate::authorize('update', $user);

        $this->openModal();
        $this->form->setUser($user);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    public function save(SaveUser $saveUser): void
    {
        $user = $this->form->user;
        $user ? Gate::authorize('update', $user) : Gate::authorize('create', User::class);

        try {
            $saveUser->execute($this->form->payload(), $user);
        } catch (QueryException $e) {
            Log::error('users.save_failed', ['user_id' => $user?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar el usuario. Inténtalo de nuevo.');
            return;
        }

        $this->dispatch('user-saved');
        $this->toastSuccess($user ? 'Usuario actualizado' : 'Usuario creado exitosamente');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.users.user-form-modal');
    }
}
