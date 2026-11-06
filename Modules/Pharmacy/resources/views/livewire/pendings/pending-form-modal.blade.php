<div>
    @php
        // Selects de contratación alimentados por características: campo => etiqueta.
        $contractingFields = [
            'eps' => 'EPS',
            'contracting_modality' => 'Modalidad de contratación',
            'invoicing_method' => 'Forma de facturación',
        ];
    @endphp

    <x-modal wire-model="show" :title="$form->pending ? 'Editar pendiente' : 'Agregar pendiente'"
             subtitle="Encabezado del pedido, requisición o mensaje interno" icon="bi-clipboard-check" size="lg">
        <div class="modal-body">
            <x-forms.section title="Datos generales" icon="bi-card-text">
                <div class="col-md-4">
                    <x-forms.select name="form.type" id="pending-type" label="Tipo" required
                                    :options="$this->typeOptions" placeholder="Seleccione un tipo" />
                </div>
                <div class="col-md-8">
                    {{-- Fuera de Oficina Principal solo existe la sede propia: el select queda bloqueado. --}}
                    <x-forms.select name="form.destination_id" id="pending-destination" label="Destino" required
                                    :options="$this->destinationOptions" placeholder="Seleccione una sede"
                                    :disabled="count($this->destinationOptions) <= 1" />
                </div>
                <div class="col-md-6">
                    <x-forms.select name="form.category" id="pending-category" label="Categoría" required
                                    :options="$this->characteristicOptions['category']" placeholder="Seleccione una opción" />
                </div>
                <div class="col-md-6"><x-forms.input name="form.reason" label="Motivo" /></div>
                <div class="col-md-6"><x-forms.input name="form.duration" label="Duración" /></div>
                <div class="col-md-6"><x-forms.input name="form.manager" label="Autoriza" /></div>
            </x-forms.section>

            <x-forms.section title="Contratación" icon="bi-file-earmark-medical">
                @foreach ($contractingFields as $field => $label)
                    <div class="col-md-4">
                        <x-forms.select name="form.{{ $field }}" id="pending-{{ $field }}" :label="$label"
                                        :options="$this->characteristicOptions[$field]" placeholder="Seleccione una opción" />
                    </div>
                @endforeach
            </x-forms.section>

            <x-forms.section title="Observaciones" icon="bi-chat-left-text">
                <div class="col-12">
                    <x-forms.textarea name="form.observations" id="pending-observations" aria-label="Observaciones" />
                </div>
            </x-forms.section>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
