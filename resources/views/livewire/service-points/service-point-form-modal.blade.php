<div>
    <x-modal wire-model="show" :title="$form->servicePoint ? 'Editar sede/servicio' : 'Agregar sede/servicio'" size="lg">
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4 mb-3"><x-forms.input name="form.code" label="Código" /></div>
                <div class="col-md-8 mb-3"><x-forms.input name="form.name" label="Nombre" /></div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Tipo</label>
                    <x-forms.select wire:model="form.type" :options="\App\Enums\ServicePointType::options()" />
                    @error('form.type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Estado</label>
                    <x-forms.select wire:model="form.status" :options="\App\Enums\ServicePointStatus::options()" />
                    @error('form.status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-12 mb-3"><x-forms.input name="form.address" label="Dirección" /></div>
                <div class="col-md-6 mb-3"><x-forms.input name="form.phone" label="Teléfono" /></div>
                <div class="col-md-3 mb-3"><x-forms.input name="form.minimum" label="Mínimo" type="number" /></div>
                <div class="col-md-3 mb-3"><x-forms.input name="form.maximum" label="Máximo" type="number" /></div>
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
