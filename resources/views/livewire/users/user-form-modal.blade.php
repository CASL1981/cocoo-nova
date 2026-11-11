<div>
    <x-modal wire-model="show" :title="$form->user ? 'Editar usuario' : 'Agregar usuario'"
             subtitle="Datos de acceso y ubicación del funcionario" icon="bi-person-badge" size="lg"
             x-on:keydown.enter.window="open && $wire.save()">

        <div class="modal-body">
            <x-forms.section title="Datos personales" icon="bi-person" description="Información básica">
                <div class="col-md-6"><x-forms.input name="form.identification" label="Identificación" required /></div>
                <div class="col-md-6"><x-forms.input name="form.email" label="Correo" type="email" required /></div>
                <div class="col-md-6"><x-forms.input name="form.name" label="Nombre" required /></div>
                <div class="col-md-6"><x-forms.input name="form.lastname" label="Apellido" required /></div>
            </x-forms.section>

            <x-forms.section title="Configuración" icon="bi-gear" description="Parámetros del sistema">
                <div class="col-md-6">
                    <x-forms.select name="form.service_point_id" label="Sede/Servicio"
                                    :options="\App\Models\ServicePoint::orderBy('name')->pluck('name', 'id')" placeholder="Seleccionar" />
                </div>
                <div class="col-md-6">
                    <x-forms.select name="form.role_id" id="role_id" label="Rol" required
                                    :options="\App\Models\Role::orderBy('name')->pluck('name', 'id')" placeholder="Seleccionar" />
                </div>
                <div class="col-md-6">
                    <x-forms.select name="form.process_id" id="process_id" label="Proceso"
                                    :options="\App\Models\Process::options()" placeholder="Seleccionar" />
                </div>
                <div class="col-md-6">
                    <x-forms.select name="form.position_id" id="position_id" label="Cargo"
                                    :options="\Modules\HumanResources\App\Models\HrCharacteristicOption::optionsFor(\Modules\HumanResources\App\Models\HrCharacteristic::POSITIONS_CODE) + ($form->user?->position_id ? [$form->user->position_id => $form->user->positionName()] : [])"
                                    placeholder="Sin cargo" hint="Talento Humano → Cargos. Aparece en las firmas del FO-300-01." />
                </div>
                <div class="col-md-6">
                    <x-forms.select name="form.status" id="status" label="Estado" required :options="\App\Enums\UserStatus::options()" />
                </div>
                <div class="col-md-6">
                    <x-forms.select name="form.can_request_supplies" id="can_request_supplies" label="Solicita suministros" required
                                    :options="['0' => 'No', '1' => 'Sí']"
                                    hint="Habilita las requisiciones de aseo, papelería y cafetería." />
                </div>
            </x-forms.section>
        </div>

        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
