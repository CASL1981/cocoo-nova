<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Livewire\Pendings;

use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Models\CharacteristicOption;
use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Pharmacy\App\Actions\SavePending;
use Modules\Pharmacy\App\Enums\PendingType;
use Modules\Pharmacy\App\Livewire\Forms\PendingForm;
use Modules\Pharmacy\App\Models\Pending;

/**
 * Modal de creación y edición del encabezado de pendientes.
 */
final class PendingFormModal extends Component
{
    use ManagesModal, Notifies;

    public PendingForm $form;

    #[On('open-pending-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', Pending::class);
        $this->openModal();

        // Por defecto el destino es la sede del solicitante.
        $this->form->destination_id = (string) $this->actor()->service_point_id;
    }

    #[On('open-pending-edit')]
    public function openEdit(int $pendingId): void
    {
        $pending = Pending::findOrFail($pendingId);
        Gate::authorize('update', $pending);

        // El modal se abre (y resetea) antes de poblar el formulario (lección #3).
        $this->openModal();
        $this->form->setPending($pending);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    /** @return array<string, string> */
    #[Computed]
    public function typeOptions(): array
    {
        return PendingType::options();
    }

    /**
     * Sedes no eliminadas como "código - nombre".
     * Solo Oficina Principal ve todas; el resto únicamente su propia sede.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function destinationOptions(): array
    {
        $actor = $this->actor();

        return ServicePoint::query()
            ->unless($actor->worksAtHeadquarters(), fn (Builder $query) => $query->whereKey($actor->service_point_id))
            ->orderBy('code')->get(['id', 'code', 'name'])
            ->mapWithKeys(fn (ServicePoint $point) => [$point->id => "{$point->code} - {$point->name}"])
            ->all();
    }

    /**
     * Opciones activas de cada característica usada en el formulario.
     *
     * @return array<string, array<string, string>>
     */
    #[Computed]
    public function characteristicOptions(): array
    {
        return [
            'category' => CharacteristicOption::forCharacteristicCode(Pending::CATEGORY_CHARACTERISTIC_CODE)->all(),
            'eps' => CharacteristicOption::forCharacteristicCode(Pending::EPS_CHARACTERISTIC_CODE)->all(),
            'contracting_modality' => CharacteristicOption::forCharacteristicCode(Pending::CONTRACTING_MODALITY_CHARACTERISTIC_CODE)->all(),
            'invoicing_method' => CharacteristicOption::forCharacteristicCode(Pending::INVOICING_METHOD_CHARACTERISTIC_CODE)->all(),
        ];
    }

    public function save(SavePending $savePending): void
    {
        $pending = $this->form->pending;
        $pending ? Gate::authorize('update', $pending) : Gate::authorize('create', Pending::class);

        try {
            $savePending->execute($this->form->payload(), $this->actor(), $pending);
        } catch (QueryException $e) {
            Log::error('pendings.save_failed', ['id' => $pending?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar el pendiente. Inténtalo de nuevo.');

            return;
        }

        $this->dispatch('pending-saved');
        $this->toastSuccess($pending ? 'Pendiente actualizado' : 'Pendiente creado exitosamente');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('pharmacy::livewire.pendings.pending-form-modal');
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
