<div>
    <x-modal wire-model="show" :title="$form->role ? 'Editar rol' : 'Agregar rol'" size="md">
        <div class="modal-body">
            <div class="mb-3">
                <x-forms.input name="form.name" label="Nombre" />
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
