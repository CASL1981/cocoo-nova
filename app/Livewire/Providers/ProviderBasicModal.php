<?php

declare(strict_types=1);

namespace App\Livewire\Providers;

use App\Actions\Providers\ProviderWorkflow;
use App\Actions\Providers\SaveProviderBasic;
use App\Enums\GeographicLevel;
use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\ProviderBasicForm;
use App\Models\CharacteristicOption;
use App\Models\GeographicLocation;
use App\Models\Provider;
use App\Models\ProviderLinkage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal del registro básico de proveedores (sin FO-300-01): compras menores a 2 SMMLV.
 *
 * Solo para procesos que no exigen el formato a todos sus proveedores (Administrativo). Al
 * registrar, si la identificación ya existe ofrece "Vincular a mi proceso"; si el proveedor
 * necesita el formato completo, pasa al modal del FO-300-01.
 *
 * @property-read Provider|null $existingProvider
 */
final class ProviderBasicModal extends Component
{
    use ManagesModal, Notifies;

    #[Locked]
    public string $scope;

    public ProviderBasicForm $form;

    // Proveedor ya registrado con la identificación digitada (solo al registrar uno nuevo).
    #[Locked]
    public ?int $existingProviderId = null;

    public function mount(string $scope): void
    {
        $this->scope = ProviderScope::from($scope)->value;
    }

    #[On('open-provider-basic-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', [ProviderLinkage::class, $this->scopeEnum()]);
        $this->openModal();
    }

    #[On('open-provider-basic-edit')]
    public function openEdit(int $linkageId): void
    {
        $linkage = ProviderLinkage::query()->with('provider')->where('scope', $this->scopeEnum())->findOrFail($linkageId);
        Gate::authorize('updateBasic', $linkage);

        // El modal se abre (y resetea) antes de poblar el formulario (lección #3).
        $this->openModal();
        $this->form->setProvider($linkage->provider, $linkage);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->reset('existingProviderId');
        $this->resetErrorBag();
    }

    /** Pasa al registro con el formato FO-300-01 completo (proveedor con compras mayores al tope). */
    public function switchToFullForm(): void
    {
        $this->closeModal();
        $this->dispatch('open-provider-create');
    }

    /** Al cambiar el tipo de persona se propone su primer tipo de documento válido. */
    public function updatedFormPersonType(): void
    {
        $types = array_map(fn (IdentityDocumentType $type) => $type->value, $this->form->personType()->documentTypes());

        if (! in_array($this->form->document_type, $types, true)) {
            $this->form->document_type = $this->form->personType() === PersonType::Natural
                ? IdentityDocumentType::CitizenshipCard->value
                : IdentityDocumentType::Nit->value;
        }
    }

    public function updatedFormDepartmentId(): void
    {
        $this->form->municipality_id = null;
    }

    /** Detecta si la identificación ya es de un proveedor (solo al registrar). */
    public function updatedFormIdentification(): void
    {
        if ($this->form->provider !== null) {
            return;
        }

        $identification = trim($this->form->identification);
        $this->existingProviderId = $identification === '' ? null : Provider::where('identification', $identification)->value('id');
    }

    #[Computed]
    public function existingProvider(): ?Provider
    {
        return $this->existingProviderId ? Provider::with('linkages')->find($this->existingProviderId) : null;
    }

    /** El proveedor existente ya tiene una vinculación vigente con este proceso. */
    #[Computed]
    public function alreadyLinked(): bool
    {
        return (bool) $this->existingProvider?->linkages
            ->contains(fn (ProviderLinkage $linkage) => $linkage->scope === $this->scopeEnum() && $linkage->status !== ProviderLinkageStatus::Rejected);
    }

    /** Vincula el proveedor existente a este proceso y abre su detalle. */
    public function linkExisting(ProviderWorkflow $workflow): void
    {
        Gate::authorize('create', [ProviderLinkage::class, $this->scopeEnum()]);

        try {
            $linkage = $workflow->link(Provider::findOrFail($this->existingProviderId), $this->scopeEnum());
        } catch (ValidationException $exception) {
            $this->addError('form.identification', collect($exception->errors())->flatten()->first());

            return;
        }

        $this->toastSuccess("Proveedor vinculado como {$this->scopeEnum()->label()}.");
        $this->redirectRoute("{$this->scopeEnum()->module()}.show", ['linkage' => $linkage->id], navigate: true);
    }

    /** @return array<int, string> */
    #[Computed]
    public function departments(): array
    {
        return GeographicLocation::childOptions(GeographicLevel::Department, GeographicLocation::colombiaId(), $this->form->department_id);
    }

    /** @return array<int, string> */
    #[Computed]
    public function municipalities(): array
    {
        return GeographicLocation::childOptions(GeographicLevel::City, $this->form->department_id, $this->form->municipality_id);
    }

    /**
     * Condiciones y plazos de pago desde Características.
     *
     * @return array<string, array<string, string>>
     */
    #[Computed]
    public function paymentOptions(): array
    {
        return [
            'condition' => CharacteristicOption::forCharacteristicCode(ProviderLinkage::PAYMENT_CONDITION_CHARACTERISTIC_CODE)->all(),
            'term' => CharacteristicOption::forCharacteristicCode(ProviderLinkage::PAYMENT_TERM_CHARACTERISTIC_CODE)->all(),
        ];
    }

    public function save(SaveProviderBasic $action): void
    {
        $linkage = $this->form->linkage;
        $linkage ? Gate::authorize('updateBasic', $linkage) : Gate::authorize('create', [ProviderLinkage::class, $this->scopeEnum()]);

        $payload = $this->form->payload();

        try {
            $action->execute($payload['provider'], $payload['terms'], $this->scopeEnum(), $linkage);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError("form.{$field}", $messages[0]);
            }

            return;
        } catch (QueryException $e) {
            Log::error('providers.basic_save_failed', ['linkage' => $linkage?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar el proveedor. Inténtalo de nuevo.');

            return;
        }

        $this->dispatch('provider-saved');
        $this->toastSuccess($linkage ? 'Proveedor actualizado' : 'Proveedor registrado y activo');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('livewire.providers.provider-basic-modal');
    }

    private function scopeEnum(): ProviderScope
    {
        return ProviderScope::from($this->scope);
    }
}
