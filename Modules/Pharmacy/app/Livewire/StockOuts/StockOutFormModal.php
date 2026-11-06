<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Livewire\StockOuts;

use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Models\CharacteristicOption;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Pharmacy\App\Actions\SaveStockOut;
use Modules\Pharmacy\App\Livewire\Forms\StockOutForm;
use Modules\Pharmacy\App\Models\StockOut;

/**
 * Modal de creación y edición de agotados.
 */
final class StockOutFormModal extends Component
{
    use ManagesModal, Notifies;

    public StockOutForm $form;

    #[On('open-stock-out-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', StockOut::class);
        $this->openModal();
    }

    #[On('open-stock-out-edit')]
    public function openEdit(int $stockOutId): void
    {
        $stockOut = StockOut::findOrFail($stockOutId);
        Gate::authorize('update', $stockOut);

        // El modal se abre (y resetea) antes de poblar el formulario (lección #3).
        $this->openModal();
        $this->form->setStockOut($stockOut);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    /** Opciones activas de la característica "Clasificación de agotados" para el select del formulario. */
    #[Computed]
    public function classificationOptions(): array
    {
        return CharacteristicOption::forCharacteristicCode(StockOut::CLASSIFICATION_CHARACTERISTIC_CODE)->all();
    }

    public function save(SaveStockOut $saveStockOut): void
    {
        $stockOut = $this->form->stockOut;
        $stockOut ? Gate::authorize('update', $stockOut) : Gate::authorize('create', StockOut::class);

        try {
            $saveStockOut->execute($this->form->payload(), $stockOut);
        } catch (QueryException $e) {
            Log::error('stock_outs.save_failed', ['id' => $stockOut?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar el agotado. Inténtalo de nuevo.');

            return;
        }

        $this->dispatch('stock-out-saved');
        $this->toastSuccess($stockOut ? 'Agotado actualizado' : 'Agotado creado exitosamente');
        $this->closeModal();
    }

    public function render()
    {
        return view('pharmacy::livewire.stock-outs.stock-out-form-modal');
    }
}
