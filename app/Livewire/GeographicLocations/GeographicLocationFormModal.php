<?php

declare(strict_types=1);

namespace App\Livewire\GeographicLocations;

use App\Actions\GeographicLocations\SaveGeographicLocation;
use App\Enums\GeographicLevel;
use App\Enums\Status;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\GeographicLocationForm;
use App\Models\GeographicLocation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal de creación y edición de países, departamentos y ciudades.
 */
final class GeographicLocationFormModal extends Component
{
    use ManagesModal, Notifies;

    public GeographicLocationForm $form;

    #[On('open-geographic-location-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', GeographicLocation::class);
        $this->openModal();
    }

    #[On('open-geographic-location-edit')]
    public function openEdit(int $locationId): void
    {
        $location = GeographicLocation::findOrFail($locationId);
        Gate::authorize('update', $location);

        // El modal se abre (y resetea) antes de poblar el formulario (lección #3).
        $this->openModal();
        $this->form->setLocation($location);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    /** Al cambiar el nivel cambia el tipo de padre: se limpia la elección anterior. */
    public function updatedFormLevel(): void
    {
        $this->form->parent_id = null;
        $this->resetErrorBag('form.parent_id');
    }

    /**
     * Ubicaciones a las que puede pertenecer según el nivel: países para un departamento y
     * departamentos (con su país) para una ciudad. Solo activas, más el padre ya guardado.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function parentOptions(): array
    {
        $parentLevel = $this->form->levelEnum()?->parentLevel();

        if ($parentLevel === null) {
            return [];
        }

        $current = $this->form->location?->parent_id;

        return GeographicLocation::query()
            ->with('parent')
            ->where('level', $parentLevel)
            ->where(fn (Builder $query) => $query->where('status', Status::Active)
                ->when($current !== null, fn (Builder $inner) => $inner->orWhere('id', $current)))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (GeographicLocation $location) => [
                $location->id => $location->parent ? "{$location->name} ({$location->parent->name})" : $location->name,
            ])
            ->all();
    }

    public function save(SaveGeographicLocation $saveLocation): void
    {
        $location = $this->form->location;
        $location ? Gate::authorize('update', $location) : Gate::authorize('create', GeographicLocation::class);

        $data = $this->form->payload();

        try {
            $saveLocation->execute($data, $location);
        } catch (ValidationException $e) {
            // Reglas de la acción (estructura bloqueada o jerarquía): se muestran en el campo.
            foreach ($e->errors() as $field => $messages) {
                $this->addError("form.{$field}", $messages[0]);
            }

            return;
        } catch (QueryException $e) {
            Log::error('geographic_locations.save_failed', ['id' => $location?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar la ubicación. Inténtalo de nuevo.');

            return;
        }

        $this->dispatch('geographic-location-saved');
        $this->toastSuccess($location ? 'Ubicación actualizada' : 'Ubicación creada exitosamente');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('livewire.geographic-locations.geographic-location-form-modal', [
            'levels' => GeographicLevel::options(),
        ]);
    }
}
