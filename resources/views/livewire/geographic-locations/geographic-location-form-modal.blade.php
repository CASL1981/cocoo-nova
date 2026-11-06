<div>
    <x-modal wire-model="show" :title="$form->location ? 'Editar ubicación' : 'Agregar ubicación'"
             subtitle="País, departamento o ciudad" icon="bi-globe-americas" size="md">
        <div class="modal-body">
            @php
                $locked = $form->lockReason !== null;
                $parentLevel = $form->levelEnum()?->parentLevel();
            @endphp

            @if ($locked)
                <div class="alert alert-info py-2 small" role="alert">
                    <i class="bi bi-lock me-1"></i>
                    El nivel, la ubicación a la que pertenece y el código no se pueden cambiar: la ubicación {{ $form->lockReason }}.
                </div>
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <x-forms.select name="form.level" id="geo-level" label="Nivel" required :options="$levels"
                                    placeholder="Seleccione" model-modifier="live" :disabled="$locked" />
                </div>
                <div class="col-md-6">
                    <x-forms.input name="form.code" id="geo-code" label="Código" required :readonly="$locked"
                                   hint="País: ISO (CO). Departamento y ciudad: DANE (23, 23001). Exterior: ISO 3166-2 o prefijo del país (AU-NSW)." />
                </div>
                @if ($parentLevel !== null)
                    <div class="col-12">
                        <x-forms.select name="form.parent_id" id="geo-parent" :label="'Pertenece a ('.mb_strtolower($parentLevel->label()).')'" required
                                        :options="$this->parentOptions" placeholder="Seleccione" :disabled="$locked" />
                    </div>
                @endif
                <div class="col-md-8"><x-forms.input name="form.name" id="geo-name" label="Nombre" required /></div>
                <div class="col-md-4">
                    <x-forms.input name="form.population" id="geo-population" label="Población" type="number" min="0" />
                </div>
            </div>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
