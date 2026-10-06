<div>
    @php
        // Un solo arreglo controla el formulario: evita repetir el mismo
        // bloque <x-forms.input> doce veces para campos que solo difieren
        // en el nombre/etiqueta.
        $fields = [
            'item' => 'Ítem (código)',
            'description' => 'Descripción',
            'brand' => 'Marca',
            'atc' => 'ATC',
            'invima' => 'INVIMA',
            'measure_unit' => 'Unidad de medida',
            'presentation' => 'Presentación',
            'concentration' => 'Concentración',
            'pharmaceutical_form' => 'Forma farmacéutica',
            'generic_name' => 'Nombre genérico',
            'generic_code' => 'Código genérico',
        ];
    @endphp

    <x-modal wire-model="show" :title="$form->product ? 'Editar producto' : 'Agregar producto'" size="xl">
        <div class="modal-body">
            <div class="row">
                @foreach ($fields as $field => $label)
                    <div class="col-md-3 mb-3">
                        <x-forms.input name="form.{{ $field }}" label="{{ $label }}" />
                    </div>
                @endforeach

                <div class="col-md-3 mb-3">
                    <x-forms.input name="form.tax_percentage" label="% de impuesto" type="number" />
                </div>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" class="btn btn-light" x-on:click="open = false">Cancelar</button>
            <button type="button" wire:click="save" class="btn btn-primary">
                Guardar <span wire:loading class="spinner-border spinner-border-sm ms-1"></span>
            </button>
        </x-slot:footer>
    </x-modal>
</div>
