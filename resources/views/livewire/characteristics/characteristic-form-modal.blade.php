{{-- resources/views/livewire/characteristics/characteristic-form-modal.blade.php --}}
<div>
    <x-modal wire-model="show" :title="$form->characteristic ? 'Editar característica' : 'Agregar característica'"
             subtitle="Lista configurable de opciones" icon="bi-list-ul" size="md">
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-12"><x-forms.input name="form.name" label="Nombre" required /></div>
                <div class="col-12">
                    @if ($form->codeLocked)
                        <x-forms.input name="form.code" label="Código interno (snake_case)" required readonly
                                       hint="No editable: la característica ya tiene opciones y la aplicación la busca por este código." />
                    @else
                        <x-forms.input name="form.code" label="Código interno (snake_case)" required
                                       hint="Ej: contracting_modality — usado internamente; deja de ser editable cuando la característica tiene opciones." />
                    @endif
                </div>
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
