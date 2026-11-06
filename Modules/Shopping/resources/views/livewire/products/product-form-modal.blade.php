<div>
    @php
        // Un solo arreglo por sección controla el formulario: campo => [etiqueta, obligatorio, columnas, tipo?, ayuda?].
        $sections = [
            ['title' => 'Identificación', 'icon' => 'bi-upc-scan', 'fields' => [
                'item' => ['Ítem (código)', true, 'col-md-3'],
                'description' => ['Descripción', true, 'col-md-9'],
            ]],
            ['title' => 'Información farmacéutica', 'icon' => 'bi-capsule', 'fields' => [
                'brand' => ['Marca', false, 'col-md-3'],
                'atc' => ['ATC', false, 'col-md-3'],
                'invima' => ['INVIMA', false, 'col-md-3'],
                'measure_unit' => ['Unidad de medida', true, 'col-md-3'],
                'presentation' => ['Presentación', false, 'col-md-4'],
                'concentration' => ['Concentración', false, 'col-md-4'],
                'pharmaceutical_form' => ['Forma farmacéutica', false, 'col-md-4'],
            ]],
            ['title' => 'Genérico', 'icon' => 'bi-diagram-3', 'fields' => [
                'generic_name' => ['Nombre genérico', true, 'col-md-8'],
                'generic_code' => ['Código genérico', true, 'col-md-4'],
            ]],
            ['title' => 'Compras', 'icon' => 'bi-truck', 'fields' => [
                'tax_percentage' => ['% de impuesto', false, 'col-md-4', 'number'],
                'supplier_nit' => ['NIT del proveedor', false, 'col-md-8', 'number', 'Proveedor principal (primera opción de compra), sin dígito de verificación.'],
            ]],
        ];
    @endphp

    <x-modal wire-model="show" :title="$form->product ? 'Editar producto' : 'Agregar producto'"
             subtitle="Datos del catálogo de productos" icon="bi-box-seam" size="xl">
        <div class="modal-body">
            @foreach ($sections as $section)
                <x-forms.section :title="$section['title']" :icon="$section['icon']">
                    @foreach ($section['fields'] as $field => $meta)
                        <div class="{{ $meta[2] }}">
                            <x-forms.input name="form.{{ $field }}" :label="$meta[0]" :required="$meta[1]"
                                           :type="$meta[3] ?? 'text'" :hint="$meta[4] ?? null" />
                        </div>
                    @endforeach
                </x-forms.section>
            @endforeach
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
