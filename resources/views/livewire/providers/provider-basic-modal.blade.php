@use('App\Enums\IdentityDocumentType')
@use('App\Enums\PersonType')

<div>
    <x-modal wire-model="show" :title="$form->provider ? 'Editar proveedor' : 'Registrar proveedor'"
             subtitle="Registro básico: compras hasta 2 SMMLV (sin FO-300-01)" icon="bi-person-plus" size="lg">
        <div class="modal-body">
            @unless ($form->provider)
                <div class="alert alert-info d-flex flex-wrap align-items-center gap-2 py-2">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <span class="me-auto small">
                        Si la orden de compra, o lo comprado en el mes, supera 2 SMMLV, el proveedor debe diligenciar el FO-300-01.
                    </span>
                    <button type="button" wire:click="switchToFullForm" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-file-earmark-text" aria-hidden="true"></i> Registrar con FO-300-01
                    </button>
                </div>
            @endunless

            <x-forms.section title="Identificación" icon="bi-person-vcard"
                             :description="$form->identityLocked ? 'La identificación de un proveedor en uso no se modifica.' : null">
                <div class="col-md-4">
                    <x-forms.select name="form.person_type" id="basic-person-type" label="Tipo de persona" required
                                    :options="PersonType::options()" model-modifier="live" :disabled="$form->identityLocked" />
                </div>
                <div class="col-md-4">
                    <x-forms.select name="form.document_type" id="basic-document-type" label="Tipo de documento" required
                                    :options="IdentityDocumentType::optionsFor($form->personType()->documentTypes())" :disabled="$form->identityLocked" />
                </div>
                <div class="col-md-4">
                    <x-forms.input name="form.identification" id="basic-identification" label="Identificación" required
                                   model-modifier="live.blur" hint="Sin puntos ni dígito de verificación." :disabled="$form->identityLocked" autocomplete="off" />
                </div>

                @if ($this->existingProvider)
                    <div class="col-12">
                        <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2 py-2 mb-0">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <span class="me-auto">
                                Ya existe <strong>{{ $this->existingProvider->name }}</strong> con esta identificación
                                ({{ $this->existingProvider->linkages->map(fn ($l) => $l->scope->label().': '.$l->status->label())->join(', ') ?: 'sin vinculaciones' }}).
                            </span>
                            @unless ($this->alreadyLinked)
                                <button type="button" wire:click="linkExisting" class="btn btn-sm btn-warning" wire:loading.attr="disabled" wire:target="linkExisting">
                                    <i class="bi bi-link-45deg" aria-hidden="true"></i> Vincular a mi proceso
                                </button>
                            @endunless
                        </div>
                    </div>
                @endif

                <div class="col-md-8">
                    <x-forms.input name="form.name" id="basic-name" label="Nombre o razón social" required />
                </div>
                <div class="col-md-4">
                    <x-forms.input name="form.contact_name" id="basic-contact" label="Contacto" />
                </div>
            </x-forms.section>

            <x-forms.section title="Ubicación y contacto" icon="bi-geo-alt">
                <div class="col-md-6">
                    <x-forms.select name="form.department_id" id="basic-department" label="Departamento" required
                                    :options="$this->departments" placeholder="Seleccione" model-modifier="live" />
                </div>
                <div class="col-md-6">
                    <x-forms.select name="form.municipality_id" id="basic-municipality" label="Municipio" required
                                    :options="$this->municipalities" placeholder="Seleccione" />
                </div>
                <div class="col-12">
                    <x-forms.input name="form.address" id="basic-address" label="Dirección" required />
                </div>
                <div class="col-md-6">
                    <x-forms.input name="form.phone" id="basic-phone" label="Teléfono / celular" required />
                </div>
                <div class="col-md-6">
                    <x-forms.input name="form.email" id="basic-email" label="Correo electrónico" type="email" required />
                </div>
            </x-forms.section>

            <x-forms.section title="Condiciones de pago" icon="bi-cash-stack">
                <div class="col-md-6">
                    <x-forms.select name="form.payment_condition" id="basic-payment-condition" label="Condición de pago"
                                    :options="$this->paymentOptions['condition']" placeholder="Sin definir" />
                </div>
                <div class="col-md-6">
                    <x-forms.select name="form.payment_term" id="basic-payment-term" label="Plazo de pago"
                                    :options="$this->paymentOptions['term']" placeholder="Sin definir" />
                </div>
            </x-forms.section>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
