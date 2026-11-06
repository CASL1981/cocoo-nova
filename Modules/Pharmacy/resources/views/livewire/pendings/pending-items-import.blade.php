<span class="d-inline-flex">
    @if ($this->worksAtHeadquarters())
        <x-table.import module="pendings" :errors-list="$importErrors" icon="bi-file-earmark-arrow-up"
                        tooltip="Importar ítems a varios pendientes activos (Oficina Principal)"
                        title="Importar ítems a varios pendientes">
            <div class="alert alert-info py-2 small">
                Cada fila se agrega al pendiente indicado en <strong>idpendiente</strong>, que debe estar
                <strong>activo</strong>. Columnas: item, idpendiente, nombreproducto, marca, centrocosto, cantidad.
            </div>
        </x-table.import>
    @endif
</span>
