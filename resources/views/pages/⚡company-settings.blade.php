<?php

use App\Actions\Settings\UpdateCompanySettings;
use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\SearchesActiveUsers;
use App\Livewire\Forms\CompanySettingsForm;
use App\Models\User;
use App\Settings\CompanySettings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Datos de la empresa (configuración global): los usan formatos, PDF y correos,
 * como la orden de compra FO-400-28. Ver con company-settings-read, editar con -update.
 */
new #[Layout('layouts.app')] #[Title('Datos de la empresa')] class extends Component
{
    use ManagesModal, Notifies, SearchesActiveUsers, WithFileUploads;

    public CompanySettingsForm $form;

    public ?TemporaryUploadedFile $logo = null;

    public bool $removeLogo = false;

    public function mount(): void
    {
        Gate::authorize('company-settings-read');
    }

    #[Computed]
    public function settings(): CompanySettings
    {
        return app(CompanySettings::class);
    }

    /** Oficial de cumplimiento configurado, para mostrarlo en la ficha. */
    #[Computed]
    public function complianceOfficer(): ?User
    {
        $id = $this->settings->compliance_officer_id;

        return $id ? User::find($id, ['id', 'name', 'lastname', 'identification']) : null;
    }

    public function selectUser(string $id): void
    {
        $user = User::find((int) $id, ['id', 'name', 'lastname', 'identification']);
        $this->form->compliance_officer_id = $user?->id;
        $this->userSearch = $user ? self::userLabel($user) : '';
    }

    public function addMinimumWage(): void
    {
        $this->form->addMinimumWage();
    }

    public function removeMinimumWage(int $index): void
    {
        $this->form->removeMinimumWage($index);
    }

    /** Quita el oficial de cumplimiento elegido en el formulario. */
    public function clearComplianceOfficer(): void
    {
        $this->form->compliance_officer_id = null;
        $this->userSearch = '';
    }

    public function openEdit(): void
    {
        Gate::authorize('company-settings-update');

        // El modal se abre (y resetea) antes de poblar el formulario (lección #3).
        $this->openModal();
        $this->form->setSettings($this->settings);
        $this->userSearch = $this->complianceOfficer ? self::userLabel($this->complianceOfficer) : '';
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->reset('logo', 'removeLogo', 'userSearch');
        $this->resetErrorBag();
    }

    public function save(UpdateCompanySettings $updateSettings): void
    {
        Gate::authorize('company-settings-update');

        $data = $this->form->payload();
        $this->validate(['logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024']]);

        try {
            $updateSettings->execute($data, $this->logo, $this->removeLogo);
        } catch (\Throwable $e) {
            Log::error('company_settings.save_failed', ['error' => $e->getMessage()]);
            $this->toastError('No se pudieron guardar los datos de la empresa. Inténtalo de nuevo.');

            return;
        }

        unset($this->settings, $this->complianceOfficer);
        $this->toastSuccess('Datos de la empresa actualizados');
        $this->closeModal();
    }
};
?>

<div x-data>
    <x-document.header title="Datos de la empresa" icon="bi-building-gear"
                       subtitle="Se usan en formatos, PDF y correos (ej. orden de compra).">
        <x-slot:actions>
            @can('company-settings-update')
                <button type="button" wire:click="openEdit" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil-square"></i> Editar
                </button>
            @endcan
        </x-slot:actions>

        <x-document.section title="Identificación" icon="bi-building">
            <x-document.field label="Razón social" :value="$this->settings->legal_name" />
            <x-document.field label="NIT" :value="$this->settings->nit" />
            <x-document.field label="Logo">
                @if ($this->settings->logoUrl())
                    <img src="{{ $this->settings->logoUrl() }}" alt="Logo de la empresa" class="img-fluid" style="max-height: 4rem;">
                @else
                    <span class="text-body-secondary">-</span>
                @endif
            </x-document.field>
        </x-document.section>

        <x-document.section title="Contacto" icon="bi-telephone">
            <x-document.field label="Dirección" :value="$this->settings->address" />
            <x-document.field label="Ciudad" :value="$this->settings->city" />
            <x-document.field label="Teléfono" :value="$this->settings->phone" />
            <x-document.field label="Correo" :value="$this->settings->email" />
            <x-document.field label="Sitio web" :value="$this->settings->website" />
        </x-document.section>

        <x-document.section title="Cumplimiento" icon="bi-shield-check">
            <x-document.field label="Oficial de cumplimiento" :value="$this->complianceOfficer?->fullName()" />
        </x-document.section>

        @php
            $year = (int) now()->year;
            $wage = $this->settings->minimumWage($year);
        @endphp
        <x-document.section title="Proveedores (FO-300-01)" icon="bi-cash-coin">
            <x-document.field label="SMMLV {{ $year }}" :value="$wage ? '$ '.number_format($wage, 0, ',', '.') : 'Sin configurar'" />
            <x-document.field label="Tope sin FO-300-01"
                              :value="$wage ? $this->settings->provider_form_wage_multiplier.' SMMLV = $ '.number_format($wage * $this->settings->provider_form_wage_multiplier, 0, ',', '.') : null" />
        </x-document.section>
    </x-document.header>

    <x-modal wire-model="show" title="Editar datos de la empresa" subtitle="Configuración global" icon="bi-building-gear">
        <div class="modal-body">
            <x-forms.section title="Identificación" icon="bi-building">
                <div class="col-md-8"><x-forms.input name="form.legal_name" id="company-legal-name" label="Razón social" required /></div>
                <div class="col-md-4"><x-forms.input name="form.nit" id="company-nit" label="NIT" required hint="Ej. 812001561-1" /></div>
                <div class="col-12">
                    <x-forms.field id="company-logo" name="logo" label="Logo" hint="PNG, JPG o WEBP, máximo 1 MB.">
                        <input id="company-logo" type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp"
                               @class(['form-control', 'is-invalid' => $errors->has('logo')])>
                    </x-forms.field>
                    @if ($this->settings->logo_path && ! $logo)
                        <div class="form-check mt-2">
                            <input id="company-remove-logo" type="checkbox" wire:model="removeLogo" class="form-check-input">
                            <label for="company-remove-logo" class="form-check-label">Quitar el logo actual</label>
                        </div>
                    @endif
                </div>
            </x-forms.section>

            <x-forms.section title="Contacto" icon="bi-telephone">
                <div class="col-md-8"><x-forms.input name="form.address" id="company-address" label="Dirección" /></div>
                <div class="col-md-4"><x-forms.input name="form.city" id="company-city" label="Ciudad" /></div>
                <div class="col-md-4"><x-forms.input name="form.phone" id="company-phone" label="Teléfono" /></div>
                <div class="col-md-4"><x-forms.input name="form.email" id="company-email" label="Correo" type="email" /></div>
                <div class="col-md-4"><x-forms.input name="form.website" id="company-website" label="Sitio web" type="url" /></div>
            </x-forms.section>

            <x-forms.section title="Proveedores (FO-300-01)" icon="bi-cash-coin"
                             description="Una orden, o lo comprado al proveedor en el mes, por encima del tope exige el FO-300-01.">
                <div class="col-md-4">
                    <x-forms.input name="form.provider_form_wage_multiplier" id="company-wage-multiplier" label="Tope (múltiplo del SMMLV)"
                                   type="number" min="1" required />
                </div>
                <div class="col-md-8">
                    <label class="form-label">SMMLV por año<span class="form-required" aria-hidden="true">*</span></label>
                    @error('form.minimum_wages')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @foreach ($form->minimum_wages as $index => $row)
                        <div class="row g-2 mb-2" wire:key="minimum-wage-{{ $index }}">
                            <div class="col-4">
                                <x-forms.input name="form.minimum_wages.{{ $index }}.year" id="company-wage-year-{{ $index }}" type="number" aria-label="Año" />
                            </div>
                            <div class="col-6">
                                <x-forms.input name="form.minimum_wages.{{ $index }}.amount" id="company-wage-amount-{{ $index }}" type="number" min="1" aria-label="SMMLV" />
                            </div>
                            <div class="col-2">
                                <x-tooltip text="Quitar año">
                                    <button type="button" wire:click="removeMinimumWage({{ $index }})" class="btn btn-outline-danger btn-table-icon" aria-label="Quitar año">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </x-tooltip>
                            </div>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addMinimumWage" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar año
                    </button>
                </div>
            </x-forms.section>

            <x-forms.section title="Cumplimiento" icon="bi-shield-check"
                             description="Revisa a los proveedores PEP antes de que Gerencia apruebe su vinculación.">
                <div class="col-md-10">
                    <x-forms.search-select name="form.compliance_officer_id" label="Oficial de cumplimiento" search="userSearch"
                                           select="selectUser" :results="$this->userResults" placeholder="Buscar usuario por nombre o identificación" />
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    @if ($form->compliance_officer_id)
                        <button type="button" wire:click="clearComplianceOfficer" class="btn btn-light w-100">Quitar</button>
                    @endif
                </div>
            </x-forms.section>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions target="save,logo" />
        </x-slot:footer>
    </x-modal>
</div>
