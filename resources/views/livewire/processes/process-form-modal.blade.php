<div>
    <x-modal wire-model="show" :title="$form->process ? 'Editar proceso' : 'Agregar proceso'"
             subtitle="Área de la organización y su director" icon="bi-diagram-3">
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-12"><x-forms.input name="form.name" id="process-name" label="Nombre" required /></div>
                <div class="col-12">
                    <x-forms.search-select name="form.director_id" id="process-director" label="Director"
                                           search="userSearch" select="selectUser" :results="$this->userResults"
                                           placeholder="Nombre, identificación o correo"
                                           hint="Aprueba las requisiciones de los integrantes del proceso." />
                    @if ($form->director_id)
                        <button type="button" wire:click="clearDirector" class="btn btn-link btn-sm px-0">
                            <i class="bi bi-x-lg"></i> Quitar director
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
    </x-modal>
</div>
