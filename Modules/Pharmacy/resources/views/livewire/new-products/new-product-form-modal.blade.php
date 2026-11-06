<div>
    <x-modal wire-model="show" :title="$form->newProduct ? 'Editar solicitud de producto nuevo' : 'Solicitar producto nuevo'"
             subtitle="Productos que aún no están codificados en el catálogo" icon="bi-bag-plus" size="lg">
        <div class="modal-body">
            <x-forms.section title="Producto" icon="bi-capsule">
                <div class="col-md-8">
                    <x-forms.input name="form.product_name" id="new-product-name" label="Nombre del producto" required
                                   model-modifier="live.debounce.300ms" autocomplete="off" />
                </div>
                <div class="col-md-4">
                    <x-forms.input name="form.brand" label="Marca" id="new-product-brand" />
                </div>

                {{-- Coincidencias: si el producto ya está codificado debe pedirse por Pendientes. --}}
                @php $suggestions = $this->suggestions; @endphp
                @if ($suggestions['catalog'] !== [] || $suggestions['linked'] !== [])
                    <div class="col-12">
                        <div class="alert alert-warning py-2 mb-0 small">
                            <div class="fw-semibold mb-1">Revise si el producto ya existe: en ese caso solicítelo en Pendientes.</div>
                            @if ($suggestions['linked'] !== [])
                                <div class="mb-1">Productos nuevos ya codificados:</div>
                                <ul class="mb-1">@foreach ($suggestions['linked'] as $text)<li>{{ $text }}</li>@endforeach</ul>
                            @endif
                            @if ($suggestions['catalog'] !== [])
                                <div class="mb-1">Coincidencias en el catálogo:</div>
                                <ul class="mb-0">@foreach ($suggestions['catalog'] as $text)<li>{{ $text }}</li>@endforeach</ul>
                            @endif
                        </div>
                    </div>
                @endif
            </x-forms.section>

            <x-forms.section title="Solicitud" icon="bi-truck">
                <div class="col-md-4">
                    <x-forms.input name="form.quantity" label="Cantidad" type="number" id="new-product-quantity" required />
                </div>
                <div class="col-md-8">
                    {{-- Fuera de Oficina Principal solo existe la sede propia: el select queda bloqueado. --}}
                    <x-forms.select name="form.destination_code" id="new-product-destination" label="Farmacia" required
                                    :options="$this->destinationOptions" placeholder="Seleccione una farmacia"
                                    :disabled="count($this->destinationOptions) <= 1" />
                </div>
                <div class="col-12">
                    <x-forms.textarea name="form.observations" id="new-product-observations" label="Observaciones" />
                </div>
            </x-forms.section>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
