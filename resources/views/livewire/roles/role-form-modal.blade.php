<div>
    <x-modal wire-model="show" :title="$form->role ? 'Editar rol' : 'Agregar rol'"
             subtitle="Los permisos se asignan desde la tarjeta del rol" icon="bi-shield-lock" size="md">
        <div class="modal-body">
            <x-forms.input name="form.name" label="Nombre" required />
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
