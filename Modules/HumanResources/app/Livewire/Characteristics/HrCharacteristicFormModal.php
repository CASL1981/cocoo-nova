<?php

declare(strict_types=1);

namespace Modules\HumanResources\App\Livewire\Characteristics;

use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\HumanResources\App\Actions\SaveHrCharacteristic;
use Modules\HumanResources\App\Livewire\Forms\HrCharacteristicForm;
use Modules\HumanResources\App\Models\HrCharacteristic;

/**
 * Modal de creación y edición de características de Talento Humano.
 */
final class HrCharacteristicFormModal extends Component
{
    use ManagesModal, Notifies;

    public HrCharacteristicForm $form;

    #[On('open-hr-characteristic-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', HrCharacteristic::class);
        $this->openModal();
    }

    #[On('open-hr-characteristic-edit')]
    public function openEdit(int $characteristicId): void
    {
        $characteristic = HrCharacteristic::findOrFail($characteristicId);
        Gate::authorize('update', $characteristic);

        $this->openModal();
        $this->form->setCharacteristic($characteristic);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    public function save(SaveHrCharacteristic $saveCharacteristic): void
    {
        $characteristic = $this->form->characteristic;
        $characteristic ? Gate::authorize('update', $characteristic) : Gate::authorize('create', HrCharacteristic::class);

        $data = $this->form->payload();

        try {
            $saveCharacteristic->execute($data, $characteristic);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError("form.{$field}", $messages[0]);
            }

            return;
        } catch (QueryException $e) {
            Log::error('hr_characteristics.save_failed', ['id' => $characteristic?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar la característica. Inténtalo de nuevo.');

            return;
        }

        $this->dispatch('hr-characteristic-saved');
        $this->toastSuccess($characteristic ? 'Característica actualizada' : 'Característica creada exitosamente');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('humanresources::livewire.characteristics.hr-characteristic-form-modal');
    }
}
