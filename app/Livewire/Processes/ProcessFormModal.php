<?php

declare(strict_types=1);

namespace App\Livewire\Processes;

use App\Actions\Processes\SaveProcess;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\SearchesActiveUsers;
use App\Livewire\Forms\ProcessForm;
use App\Models\Process;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal de creación y edición de procesos (nombre y director).
 */
final class ProcessFormModal extends Component
{
    use ManagesModal, Notifies, SearchesActiveUsers;

    public ProcessForm $form;

    #[On('open-process-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', Process::class);
        $this->openModal();
    }

    #[On('open-process-edit')]
    public function openEdit(int $processId): void
    {
        $process = Process::with('director:id,name,lastname,identification')->findOrFail($processId);
        Gate::authorize('update', $process);

        // El modal se abre (y resetea) antes de poblar el formulario (lección #3).
        $this->openModal();
        $this->form->setProcess($process);
        $this->userSearch = $process->director ? self::userLabel($process->director) : '';
    }

    public function selectUser(string $id): void
    {
        $this->form->director_id = $id;
        $this->userSearch = $this->userResults[(int) $id] ?? $this->userSearch;
    }

    /** Quita el director elegido. */
    public function clearDirector(): void
    {
        $this->form->director_id = null;
        $this->userSearch = '';
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->userSearch = '';
        $this->resetErrorBag();
    }

    public function save(SaveProcess $saveProcess): void
    {
        $process = $this->form->process;
        $process ? Gate::authorize('update', $process) : Gate::authorize('create', Process::class);

        try {
            $saveProcess->execute($this->form->payload(), $process);
        } catch (QueryException $e) {
            Log::error('processes.save_failed', ['id' => $process?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar el proceso. Inténtalo de nuevo.');

            return;
        }

        $this->dispatch('process-saved');
        $this->toastSuccess($process ? 'Proceso actualizado' : 'Proceso creado exitosamente');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('livewire.processes.process-form-modal');
    }
}
