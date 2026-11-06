<?php

declare(strict_types=1);

namespace App\Livewire\Processes;

use App\Actions\Processes\SaveProcessSubstitution;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\SearchesActiveUsers;
use App\Livewire\Forms\ProcessSubstitutionForm;
use App\Models\Process;
use App\Models\ProcessSubstitution;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal de creación y edición de reemplazos de directores de proceso.
 */
final class ProcessSubstitutionFormModal extends Component
{
    use ManagesModal, Notifies, SearchesActiveUsers;

    public ProcessSubstitutionForm $form;

    #[On('open-process-substitution-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', ProcessSubstitution::class);
        $this->openModal();
    }

    #[On('open-process-substitution-edit')]
    public function openEdit(int $substitutionId): void
    {
        $substitution = ProcessSubstitution::with('substitute:id,name,lastname,identification')->findOrFail($substitutionId);
        Gate::authorize('update', $substitution);

        // El modal se abre (y resetea) antes de poblar el formulario (lección #3).
        $this->openModal();
        $this->form->setSubstitution($substitution);
        $this->userSearch = $substitution->substitute ? self::userLabel($substitution->substitute) : '';
    }

    public function selectUser(string $id): void
    {
        $this->form->substitute_id = $id;
        $this->userSearch = $this->userResults[(int) $id] ?? $this->userSearch;
    }

    /** @return array<int, string> */
    #[Computed]
    public function processOptions(): array
    {
        return Process::options();
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->userSearch = '';
        $this->resetErrorBag();
    }

    public function save(SaveProcessSubstitution $saveSubstitution): void
    {
        $substitution = $this->form->substitution;
        $substitution ? Gate::authorize('update', $substitution) : Gate::authorize('create', ProcessSubstitution::class);

        try {
            $saveSubstitution->execute($this->form->payload(), $substitution);
        } catch (QueryException $e) {
            Log::error('process_substitutions.save_failed', ['id' => $substitution?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar el reemplazo. Inténtalo de nuevo.');

            return;
        }

        $this->dispatch('process-substitution-saved');
        $this->toastSuccess($substitution ? 'Reemplazo actualizado' : 'Reemplazo registrado exitosamente');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('livewire.processes.process-substitution-form-modal');
    }
}
