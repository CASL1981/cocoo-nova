<?php

declare(strict_types=1);

namespace Modules\HumanResources\App\Livewire\Characteristics;

use App\Enums\Status;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Models\Process;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\HumanResources\App\Actions\SaveHrCharacteristicOption;
use Modules\HumanResources\App\Livewire\Forms\HrCharacteristicOptionForm;
use Modules\HumanResources\App\Models\HrCharacteristic;
use Modules\HumanResources\App\Models\HrCharacteristicOption;

/**
 * Modal de creación y edición de opciones de Talento Humano. La característica de una opción nueva
 * es la elegida en el panel izquierdo (evento 'hr-characteristic-selected').
 */
final class HrCharacteristicOptionFormModal extends Component
{
    use ManagesModal, Notifies;

    public HrCharacteristicOptionForm $form;

    // Característica elegida en el panel izquierdo; solo la asigna el servidor.
    #[Locked]
    public ?int $selectedCharacteristicId = null;

    #[On('hr-characteristic-selected')]
    public function rememberCharacteristic(int $characteristicId): void
    {
        $this->selectedCharacteristicId = $characteristicId;
    }

    #[On('open-hr-characteristic-option-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', HrCharacteristicOption::class);

        $characteristic = $this->selectedCharacteristicId === null ? null : HrCharacteristic::find($this->selectedCharacteristicId);

        if ($characteristic === null) {
            $this->toastError('Elige una característica activa en el panel izquierdo para agregarle opciones.');

            return;
        }

        $this->openModal();
        $this->form->characteristicId = $characteristic->id;
        $this->form->isPositions = $characteristic->isPositions();
    }

    #[On('open-hr-characteristic-option-edit')]
    public function openEdit(int $optionId): void
    {
        $option = HrCharacteristicOption::with('characteristic')->findOrFail($optionId);
        Gate::authorize('update', $option);

        $this->openModal();
        $this->form->setOption($option);
        $this->form->isPositions = (bool) $option->characteristic?->isPositions();
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    /** @return array<int, string> */
    #[Computed]
    public function processes(): array
    {
        $current = $this->form->process_id;

        return Process::query()
            ->where(fn (Builder $query) => $query->where('status', Status::Active)
                ->when($current !== null && $current !== '', fn (Builder $inner) => $inner->orWhere('id', (int) $current)))
            ->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * Cargos que pueden ser jefe inmediato: los activos de la misma característica, sin el propio.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function parents(): array
    {
        $current = $this->form->parent_id;

        return HrCharacteristicOption::query()
            ->where('hr_characteristic_id', $this->form->characteristicId)
            ->when($this->form->option !== null, fn (Builder $query) => $query->whereKeyNot($this->form->option->id))
            ->where(fn (Builder $query) => $query->where('status', Status::Active)
                ->when($current !== null && $current !== '', fn (Builder $inner) => $inner->orWhere('id', (int) $current)))
            ->orderBy('name')->pluck('name', 'id')->all();
    }

    public function save(SaveHrCharacteristicOption $saveOption): void
    {
        $option = $this->form->option;
        $option ? Gate::authorize('update', $option) : Gate::authorize('create', HrCharacteristicOption::class);

        $data = $this->form->payload();

        try {
            $saveOption->execute($data, $option);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError("form.{$field}", $messages[0]);
            }

            return;
        } catch (QueryException $e) {
            Log::error('hr_characteristic_options.save_failed', ['id' => $option?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar la opción. Inténtalo de nuevo.');

            return;
        }

        $this->dispatch('hr-characteristic-option-saved');
        $this->toastSuccess($option ? 'Opción actualizada' : 'Opción creada exitosamente');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('humanresources::livewire.characteristics.hr-characteristic-option-form-modal');
    }
}
