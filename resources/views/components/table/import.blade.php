{{-- Botón + modal de importación Excel/CSV; el componente Livewire debe usar el trait ImportsRows. --}}
@props([
    'module',            // Slug de permisos (ej. products): exige {module}-import.
    'errorsList' => [],  // $importErrors del componente.
    'keyLabel' => null,  // Etiqueta de la columna clave, para el texto de ayuda.
    'tooltip' => 'Importar desde Excel / CSV',
    'title' => 'Importar registros',
    'label' => null,     // Texto opcional junto al ícono del botón.
    'icon' => 'bi-upload', // Ícono del botón.
    'button' => true,    // false: solo el modal (el botón lo pinta la barra de acciones).
])

@can("{$module}-import")
    @if ($button)
        <x-tooltip :text="$tooltip">
            <button type="button" wire:click="openImport" @class(["btn btn-sm btn-outline-primary", "btn-table-icon" => ! $label]) aria-label="{{ $tooltip }}">
                <i class="bi {{ $icon }}"></i>
                @if ($label)
                    {{ $label }}
                @endif
            </button>
        </x-tooltip>
    @endif

    <x-modal wire-model="showImport" :title="$title" subtitle="Excel (.xlsx) o CSV con los encabezados de la plantilla" icon="bi-file-earmark-arrow-up" size="lg">
        <div class="modal-body">
            {{ $slot }}
            <p class="mb-2">
                Cargue un archivo Excel (.xlsx) o CSV con la fila de encabezados de la plantilla.
                @if ($keyLabel)
                    Los registros con un <strong>{{ $keyLabel }}</strong> existente se actualizan; los demás se crean.
                @else
                    Cada fila crea un registro nuevo.
                @endif
                Si alguna fila tiene errores no se guarda ningún registro.
            </p>
            <button type="button" wire:click="downloadImportTemplate" class="btn btn-sm btn-link px-0 mb-3">
                <i class="bi bi-download"></i> Descargar plantilla
            </button>

            <input type="file" wire:model="importFile" accept=".xlsx,.csv"
                   @class(['form-control', 'is-invalid' => $errors->has('importFile')])>
            @error('importFile')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div wire:loading wire:target="importFile" class="small text-muted mt-1">Cargando archivo…</div>
            <div wire:loading wire:target="import" class="small text-muted mt-1">Validando e importando, puede tardar unos segundos…</div>

            @if ($errorsList !== [])
                <div class="alert alert-danger mt-3 mb-0" style="max-height: 16rem; overflow-y: auto;">
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errorsList as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
        </div>

        <x-slot:footer>
            <x-forms.modal-actions submit="import" submit-label="Importar" submit-icon="bi-upload"
                                   target="import,importFile" cancel="closeImport" />
        </x-slot:footer>
    </x-modal>
@endcan
