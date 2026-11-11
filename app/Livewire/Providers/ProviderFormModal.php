<?php

declare(strict_types=1);

namespace App\Livewire\Providers;

use App\Actions\Providers\ProviderWorkflow;
use App\Actions\Providers\SaveProviderVersion;
use App\Enums\GeographicLevel;
use App\Enums\IdentityDocumentType;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\ProviderForm;
use App\Models\CharacteristicOption;
use App\Models\EconomicActivity;
use App\Models\GeographicLocation;
use App\Models\Provider;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
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
 * Modal del formato FO-300-01: registra un proveedor nuevo o edita el Borrador abierto.
 *
 * Lo comparten las pantallas de proveedores de cada proceso (tipo fijado en $scope). Al
 * registrar, si la identificación ya existe ofrece "Vincular a mi proceso" para reutilizar
 * el formulario vigente en lugar de duplicar al tercero.
 *
 * @property-read Provider|null $existingProvider
 */
final class ProviderFormModal extends Component
{
    use ManagesModal, Notifies;

    /** Máximo de actividades CIIU sugeridas en el buscador. */
    private const ACTIVITY_RESULTS_LIMIT = 15;

    #[Locked]
    public string $scope;

    public ProviderForm $form;

    // Proveedor ya registrado con la identificación digitada (solo al registrar uno nuevo).
    #[Locked]
    public ?int $existingProviderId = null;

    // Proveedor aprobado sin Borrador: el modal pide confirmar la apertura de su actualización.
    #[Locked]
    public ?int $updateLinkageId = null;

    // Textos de los buscadores CIIU; el id elegido vive en el formulario.
    public string $activitySearch = '';

    public string $employerActivitySearch = '';

    public function mount(string $scope): void
    {
        $this->scope = ProviderScope::from($scope)->value;
    }

    #[On('open-provider-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', [ProviderLinkage::class, $this->scopeEnum()]);

        $this->openModal();
        $this->form->contacts = ProviderForm::emptyContacts();
        $this->form->country_id = (string) GeographicLocation::colombiaId();
    }

    #[On('open-provider-edit')]
    public function openEdit(int $linkageId): void
    {
        $linkage = $this->findLinkage($linkageId);

        // Proveedor básico (sin FO-300-01): se edita en el modal del registro básico.
        if (Gate::allows('updateBasic', $linkage)) {
            $this->dispatch('open-provider-basic-edit', linkageId: $linkage->id);

            return;
        }

        // Un proveedor aprobado no se edita directamente: se ofrece abrir su actualización.
        if (Gate::denies('update', $linkage) && Gate::allows('startUpdate', $linkage)) {
            $this->openModal();
            $this->updateLinkageId = $linkage->id;

            return;
        }

        Gate::authorize('update', $linkage);

        // El modal se abre (y resetea) antes de poblar el formulario (lección #3).
        $this->openModal();
        $this->fillDraft($linkage);
    }

    /** Crea la versión de actualización en Borrador y carga el formulario para editarla. */
    public function startUpdate(ProviderWorkflow $workflow): void
    {
        $linkage = $this->findLinkage((int) $this->updateLinkageId);
        Gate::authorize('startUpdate', $linkage);

        try {
            $draft = $workflow->startUpdate($linkage->provider);
        } catch (ValidationException $exception) {
            $this->toastError(collect($exception->errors())->flatten()->first());
            $this->closeModal();

            return;
        }

        $this->updateLinkageId = null;
        $this->fillDraft($this->findLinkage($linkage->id));
        $this->dispatch('provider-saved');
        $this->toastSuccess("Actualización abierta: versión {$draft->number} en Borrador.");
    }

    /** Carga en el formulario el Borrador abierto del proveedor. */
    private function fillDraft(ProviderLinkage $linkage): void
    {
        $version = $linkage->provider->openVersion()
            ->with(['provider', 'contacts', 'economicActivity', 'employerEconomicActivity'])->firstOrFail();
        $this->form->setVersion($version);
        $this->activitySearch = (string) $version->economicActivity?->label();
        $this->employerActivitySearch = (string) $version->employerEconomicActivity?->label();
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->reset('existingProviderId', 'updateLinkageId', 'activitySearch', 'employerActivitySearch');
        $this->resetErrorBag();
    }

    /** Al cambiar el tipo de persona se propone su primer tipo de documento válido. */
    public function updatedFormPersonType(): void
    {
        $types = array_map(fn (IdentityDocumentType $type) => $type->value, $this->form->personType()->documentTypes());

        if (! in_array($this->form->document_type, $types, true)) {
            $this->form->document_type = $this->form->isNatural() ? IdentityDocumentType::CitizenshipCard->value : IdentityDocumentType::Nit->value;
        }
    }

    public function updatedFormDepartmentId(): void
    {
        $this->form->municipality_id = null;
    }

    public function updatedFormBirthDepartmentId(): void
    {
        $this->form->birth_municipality_id = null;
    }

    /** Detecta si la identificación ya es de un proveedor (solo al registrar). */
    public function updatedFormIdentification(): void
    {
        if ($this->form->version !== null) {
            return;
        }

        $identification = trim($this->form->identification);
        $this->existingProviderId = $identification === ''
            ? null
            : Provider::where('identification', $identification)->value('id');
    }

    /** Proveedor existente con sus vinculaciones, para el aviso de "Vincular a mi proceso". */
    #[Computed]
    public function existingProvider(): ?Provider
    {
        return $this->existingProviderId
            ? Provider::with('linkages')->find($this->existingProviderId)
            : null;
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

        $provider = Provider::findOrFail($this->existingProviderId);

        try {
            $linkage = $workflow->link($provider, $this->scopeEnum());
        } catch (ValidationException $exception) {
            $this->addError('form.identification', collect($exception->errors())->flatten()->first());

            return;
        }

        $this->toastSuccess("Proveedor vinculado como {$this->scopeEnum()->label()}: complete y envíe la actualización.");
        $this->redirectRoute("{$this->scopeEnum()->module()}.show", ['linkage' => $linkage->id], navigate: true);
    }

    public function selectActivity(string $id): void
    {
        $activity = EconomicActivity::find((int) $id);
        $this->form->economic_activity_id = $activity ? (string) $activity->id : null;
        $this->activitySearch = (string) $activity?->label();
    }

    public function selectEmployerActivity(string $id): void
    {
        $activity = EconomicActivity::find((int) $id);
        $this->form->employer_economic_activity_id = $activity ? (string) $activity->id : null;
        $this->employerActivitySearch = (string) $activity?->label();
    }

    /** @return array<int, string> */
    #[Computed]
    public function activityResults(): array
    {
        return $this->searchActivities($this->activitySearch);
    }

    /** @return array<int, string> */
    #[Computed]
    public function employerActivityResults(): array
    {
        return $this->searchActivities($this->employerActivitySearch);
    }

    /** @return array<int, string> */
    #[Computed]
    public function departments(): array
    {
        return GeographicLocation::childOptions(GeographicLevel::Department, GeographicLocation::colombiaId(), $this->form->department_id)
            + GeographicLocation::childOptions(GeographicLevel::Department, GeographicLocation::colombiaId(), $this->form->birth_department_id);
    }

    /** @return array<int, string> */
    #[Computed]
    public function municipalities(): array
    {
        return GeographicLocation::childOptions(GeographicLevel::City, $this->form->department_id, $this->form->municipality_id);
    }

    /** @return array<int, string> */
    #[Computed]
    public function birthMunicipalities(): array
    {
        return GeographicLocation::childOptions(GeographicLevel::City, $this->form->birth_department_id, $this->form->birth_municipality_id);
    }

    /** @return array<int, string> */
    #[Computed]
    public function countries(): array
    {
        return GeographicLocation::options(GeographicLevel::Country, $this->form->country_id)
            + GeographicLocation::options(GeographicLevel::Country, $this->form->nationality_country_id);
    }

    /**
     * Listas del formato desde Características, por código.
     *
     * @return array<string, array<string, string>>
     */
    #[Computed]
    public function characteristicOptions(): array
    {
        return collect([
            'occupation' => ProviderVersion::OCCUPATION_CHARACTERISTIC_CODE,
            'education_level' => ProviderVersion::EDUCATION_LEVEL_CHARACTERISTIC_CODE,
            'protection_group' => ProviderVersion::PROTECTION_GROUP_CHARACTERISTIC_CODE,
            'tax_regime' => ProviderVersion::TAX_REGIME_CHARACTERISTIC_CODE,
            'company_type' => ProviderVersion::COMPANY_TYPE_CHARACTERISTIC_CODE,
            'relationship' => ProviderVersion::RELATIONSHIP_CHARACTERISTIC_CODE,
        ])->map(fn (string $code) => CharacteristicOption::forCharacteristicCode($code)->all())->all();
    }

    public function save(SaveProviderVersion $action): void
    {
        $version = $this->form->version;
        $linkage = $version ? $this->linkageOf($version) : null;
        $linkage ? Gate::authorize('update', $linkage) : Gate::authorize('create', [ProviderLinkage::class, $this->scopeEnum()]);

        $payload = $this->form->payload();

        try {
            $saved = $action->execute($payload['data'], $payload['contacts'], $this->scopeEnum(), $version);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError("form.{$field}", $messages[0]);
            }

            return;
        } catch (QueryException $e) {
            Log::error('providers.save_failed', ['version' => $version?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar el proveedor. Inténtalo de nuevo.');

            return;
        }

        if ($version === null) {
            // Proveedor nuevo: se abre su detalle para cargar accionistas, familiares PEP y anexos.
            $this->toastSuccess('Proveedor registrado en Borrador');
            $this->redirectRoute("{$this->scopeEnum()->module()}.show", ['linkage' => $this->linkageOf($saved)->id], navigate: true);

            return;
        }

        $this->dispatch('provider-saved');
        $this->toastSuccess('Borrador actualizado');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('livewire.providers.provider-form-modal');
    }

    private function scopeEnum(): ProviderScope
    {
        return ProviderScope::from($this->scope);
    }

    /** Vinculación de este proceso; nunca la de otro tipo. */
    private function findLinkage(int $linkageId): ProviderLinkage
    {
        return ProviderLinkage::query()->with('provider')->where('scope', $this->scopeEnum())->findOrFail($linkageId);
    }

    private function linkageOf(ProviderVersion $version): ProviderLinkage
    {
        return ProviderLinkage::query()->with('provider')
            ->where('provider_id', $version->provider_id)->where('scope', $this->scopeEnum())->firstOrFail();
    }

    /** @return array<int, string> */
    private function searchActivities(string $term): array
    {
        $term = trim($term);

        return EconomicActivity::query()
            ->when($term !== '', fn ($query) => $query->search($term))
            ->orderBy('code')->limit(self::ACTIVITY_RESULTS_LIMIT)->get()
            ->mapWithKeys(fn (EconomicActivity $activity) => [$activity->id => $activity->label()])
            ->all();
    }
}
