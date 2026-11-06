<div>
    <x-modal wire-model="show" :title="$form->stockOut ? 'Editar agotado' : 'Agregar agotado'"
             subtitle="Producto agotado según circular" icon="bi-exclamation-octagon" size="lg">
        <div class="modal-body">
            <x-forms.section title="Producto" icon="bi-capsule">
                <div class="col-md-4"><x-forms.input name="form.generic_code" label="Código genérico" required /></div>
                <div class="col-md-8"><x-forms.input name="form.generic_name" label="Nombre genérico" required /></div>
                <div class="col-md-8"><x-forms.input name="form.product_name" label="Nombre del producto" required /></div>
                <div class="col-md-4"><x-forms.input name="form.brand" label="Marca" /></div>
            </x-forms.section>

            <x-forms.section title="Circular" icon="bi-file-earmark-text">
                <div class="col-md-4"><x-forms.input name="form.circular_number" label="Número de circular" required /></div>
                <div class="col-md-4"><x-forms.input name="form.circular_date" label="Fecha de circular" type="date" required /></div>
                <div class="col-md-4">
                    <x-forms.select name="form.classification" label="Clasificación"
                                    :options="$this->classificationOptions" placeholder="Seleccione una clasificación" />
                </div>
            </x-forms.section>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
