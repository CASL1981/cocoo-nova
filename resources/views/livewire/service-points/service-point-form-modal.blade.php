<div>
    <x-modal wire-model="show" :title="$form->servicePoint ? 'Editar sede/servicio' : 'Agregar sede/servicio'"
             subtitle="Sedes y servicios de la organización" icon="bi-building" size="lg">
        <div class="modal-body">
            <x-forms.section title="Identificación" icon="bi-card-heading">
                <div class="col-md-3"><x-forms.input name="form.code" label="Código" required /></div>
                <div class="col-md-6"><x-forms.input name="form.name" label="Nombre" required /></div>
                <div class="col-md-3"><x-forms.input name="form.abbreviation" label="Abreviatura" maxlength="20" required /></div>
                <div class="col-md-6">
                    <x-forms.select name="form.type" label="Tipo" required :options="\App\Enums\ServicePointType::options()" />
                </div>
                <div class="col-md-6">
                    <x-forms.select name="form.status" label="Estado" required :options="\App\Enums\ServicePointStatus::options()" />
                </div>
            </x-forms.section>

            <x-forms.section title="Contacto y capacidad" icon="bi-geo-alt">
                <div class="col-12"><x-forms.input name="form.address" label="Dirección" /></div>
                <div class="col-md-6"><x-forms.input name="form.phone" label="Teléfono" /></div>
                <div class="col-md-3"><x-forms.input name="form.minimum" label="Mínimo" type="number" /></div>
                <div class="col-md-3"><x-forms.input name="form.maximum" label="Máximo" type="number" /></div>
            </x-forms.section>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
