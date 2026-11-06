<div>
    <x-modal wire-model="show" :title="$form->option ? ($form->isPositions ? 'Editar cargo' : 'Editar opción') : ($form->isPositions ? 'Agregar cargo' : 'Agregar opción')"
             :subtitle="$form->isPositions ? 'Cargo del organigrama' : 'Opción de la característica'" icon="bi-person-badge" size="md">
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-12"><x-forms.input name="form.name" id="hr-option-name" label="Nombre" required /></div>
                @if ($form->isPositions)
                    <div class="col-12">
                        <x-forms.select name="form.process_id" id="hr-option-process" label="Proceso (dirección)" required
                                        :options="$this->processes" placeholder="Seleccione" />
                    </div>
                    <div class="col-12">
                        <x-forms.select name="form.parent_id" id="hr-option-parent" label="Jefe inmediato"
                                        :options="$this->parents" placeholder="Sin jefe inmediato"
                                        hint="Cargo al que reporta según el organigrama." />
                    </div>
                @endif
                <div class="col-12">
                    <x-forms.select name="form.status" id="hr-option-status" label="Estado" required :options="\App\Enums\Status::options()" />
                </div>
            </div>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
