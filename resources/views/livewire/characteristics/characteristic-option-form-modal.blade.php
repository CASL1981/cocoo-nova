<div>
    <x-modal wire-model="show" :title="$form->option ? 'Editar opción' : 'Agregar opción'"
             subtitle="Valor disponible en los formularios" icon="bi-ui-radios" size="md">
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-12"><x-forms.input name="form.name" label="Nombre" required /></div>
                <div class="col-12">
                    <x-forms.select name="form.status" label="Estado" required :options="\App\Enums\Status::options()" />
                </div>
            </div>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
