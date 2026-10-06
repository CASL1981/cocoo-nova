<div>
    <x-modal wire-model="show" :title="$form->user ? 'Editar usuario' : 'Agregar usuario'" size="lg"
             x-on:keydown.enter.window="open && $wire.save()">

        <div class="modal-body">
            <div class="row">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="avatar avatar-40 bg-primary-subtle text-primary rounded">
                        <i class="bi bi-person"></i>
                    </span>
                    <div>
                        <h6 class="mb-0">Datos personales</h6>
                        <small class="text-muted">Información básica</small>
                    </div>
                </div>
                <div class="col-md-6 mb-3"><x-forms.input name="form.identification" label="Identificación" /></div>
                <div class="col-md-6 mb-3"><x-forms.input name="form.name" label="Nombre" /></div>
                <div class="col-md-6 mb-3"><x-forms.input name="form.lastname" label="Apellido" /></div>
                <div class="col-md-6 mb-3"><x-forms.input name="form.email" label="Correo" type="email" /></div>
                <div class="d-flex align-items-center gap-2 mb-3 mt-4">
                    <span class="avatar avatar-40 bg-primary-subtle text-primary rounded">
                        <i class="bi bi-gear"></i>
                    </span>
                    <div>
                        <h6 class="mb-0">Configuración</h6>
                        <small class="text-muted">Parametros del sistema</small>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sede/Servicio</label>
                    <x-forms.select wire:model="form.service_point_id"
                    :options="\App\Models\ServicePoint::orderBy('name')->pluck('name', 'id')" placeholder="Seleccionar" />
                    @error('form.service_point_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="role_id">Role</label>
                    <x-forms.select id="role_id" wire:model="form.role_id"
                    :options="\App\Models\Role::orderBy('name')->pluck('name', 'id')"
                    placeholder="Seleccionar"/>
                    @error('form.role_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="area">Area</label>
                    <x-forms.select id="area" wire:model="form.area"
                    :options="['Administrativa' => 'Administrativa', 'Comercial' => 'Comercial', 'Farmacia' => 'Farmacia', 'Financiero' => 'Financiero']" />
                    @error('form.area') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label" for="status">Estado</label>
                    <x-forms.select id="status" wire:model="form.status" :options="\App\Enums\UserStatus::options()" />
                    @error('form.status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <x-slot:footer>
            <button type="button" class="btn btn-light px-4" x-on:click="open = false">Cancelar</button>
            <button type="button" wire:click="save" class="btn btn-primary px-4">
                Guardar
                <span wire:loading class="spinner-border spinner-border-sm ms-1"></span>
            </button>
        </x-slot:footer>
    </x-modal>
</div>
