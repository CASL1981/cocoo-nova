<?php

// app/Livewire/Characteristics/CharacteristicFormModal.php

namespace App\Livewire\Characteristics;

use App\Actions\Characteristics\SaveCharacteristic;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\CharacteristicForm;
use App\Models\Characteristic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class CharacteristicFormModal extends Component
{
    use ManagesModal, Notifies;

    public CharacteristicForm $form;

    #[On('open-characteristic-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', Characteristic::class);
        $this->openModal();
    }

    #[On('open-characteristic-edit')]
    public function openEdit(int $characteristicId): void
    {
        $characteristic = Characteristic::findOrFail($characteristicId);
        Gate::authorize('update', $characteristic);

        $this->openModal();
        $this->form->setCharacteristic($characteristic);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    public function save(SaveCharacteristic $saveCharacteristic): void
    {
        $characteristic = $this->form->characteristic;
        $characteristic ? Gate::authorize('update', $characteristic) : Gate::authorize('create', Characteristic::class);

        $data = $this->form->payload();

        try {
            $saveCharacteristic->execute($data, $characteristic);
        } catch (ValidationException $e) {
            // Regla de negocio de la acción (código en uso): se muestra en el campo del formulario.
            foreach ($e->errors() as $field => $messages) {
                $this->addError("form.{$field}", $messages[0]);
            }

            return;
        } catch (QueryException $e) {
            Log::error('characteristics.save_failed', ['id' => $characteristic?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar la característica. Inténtalo de nuevo.');

            return;
        }

        $this->dispatch('characteristic-saved');
        $this->toastSuccess($characteristic ? 'Característica actualizada' : 'Característica creada exitosamente');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('livewire.characteristics.characteristic-form-modal');
    }
}
