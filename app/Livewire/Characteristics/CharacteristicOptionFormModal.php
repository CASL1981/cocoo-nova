<?php

// app/Livewire/Characteristics/CharacteristicOptionFormModal.php

namespace App\Livewire\Characteristics;

use App\Actions\Characteristics\SaveCharacteristicOption;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\CharacteristicOptionForm;
use App\Models\CharacteristicOption;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class CharacteristicOptionFormModal extends Component
{
    use ManagesModal, Notifies;

    public CharacteristicOptionForm $form;

    #[On('open-characteristic-option-create')]
    public function openCreate(int $characteristicId): void
    {
        Gate::authorize('create', CharacteristicOption::class);
        $this->openModal();
        $this->form->characteristicId = $characteristicId;
    }

    #[On('open-characteristic-option-edit')]
    public function openEdit(int $optionId): void
    {
        $option = CharacteristicOption::findOrFail($optionId);
        Gate::authorize('update', $option);

        $this->openModal();
        $this->form->setOption($option);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    public function save(SaveCharacteristicOption $saveOption): void
    {
        $option = $this->form->option;
        $option ? Gate::authorize('update', $option) : Gate::authorize('create', CharacteristicOption::class);

        try {
            $saveOption->execute($this->form->payload(), $option);
        } catch (QueryException $e) {
            Log::error('characteristic_options.save_failed', ['id' => $option?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar la opción. Inténtalo de nuevo.');

            return;
        }

        $this->dispatch('characteristic-option-saved');
        $this->toastSuccess($option ? 'Opción actualizada' : 'Opción creada exitosamente');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('livewire.characteristics.characteristic-option-form-modal');
    }
}
