<?php

namespace App\Livewire\ServicePoints;

use App\Actions\ServicePoints\SaveServicePoint;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\ServicePointForm;
use App\Models\ServicePoint;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class ServicePointFormModal extends Component
{
    use ManagesModal, Notifies;

    public ServicePointForm $form;

    #[On('open-service-point-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', ServicePoint::class);
        $this->openModal();
    }

    #[On('open-service-point-edit')]
    public function openEdit(int $servicePointId): void
    {
        $servicePoint = ServicePoint::findOrFail($servicePointId);
        Gate::authorize('update', $servicePoint);

        $this->openModal();
        $this->form->setServicePoint($servicePoint);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    public function save(SaveServicePoint $saveServicePoint): void
    {
        $servicePoint = $this->form->servicePoint;
        $servicePoint ? Gate::authorize('update', $servicePoint) : Gate::authorize('create', ServicePoint::class);

        try {
            $saveServicePoint->execute($this->form->payload(), $servicePoint);
        } catch (QueryException $e) {
            Log::error('service_points.save_failed', ['id' => $servicePoint?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar el centro de costos. Inténtalo de nuevo.');
            return;
        }

        $this->dispatch('service-point-saved');
        $this->toastSuccess($servicePoint ? 'Centro de costos actualizado' : 'Centro de costos creado exitosamente');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.service-points.service-point-form-modal');
    }
}
