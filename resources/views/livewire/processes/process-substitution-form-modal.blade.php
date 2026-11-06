<div>
    <x-modal wire-model="show" :title="$form->substitution ? 'Editar reemplazo' : 'Agregar reemplazo'"
             subtitle="Persona que aprueba en lugar del director del proceso" icon="bi-person-gear">
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <x-forms.select name="form.process_id" id="substitution-process" label="Proceso" required
                                    :options="$this->processOptions" placeholder="Seleccionar" />
                </div>
                <div class="col-md-6">
                    <x-forms.search-select name="form.substitute_id" id="substitution-user" label="Reemplazo" required
                                           search="userSearch" select="selectUser" :results="$this->userResults"
                                           placeholder="Nombre, identificación o correo" />
                </div>
                <div class="col-md-6"><x-forms.input name="form.starts_on" id="substitution-starts-on" label="Desde" type="date" required /></div>
                <div class="col-md-6">
                    <x-forms.input name="form.ends_on" id="substitution-ends-on" label="Hasta" type="date"
                                   hint="Vacío: asignación permanente." />
                </div>
                <div class="col-12">
                    <x-forms.textarea name="form.observations" id="substitution-observations" label="Observaciones" rows="2" />
                </div>
            </div>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
