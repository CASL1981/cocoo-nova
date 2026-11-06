{{--
    Barra estándar de acciones de un listado; cada botón respeta el permiso {module}-{acción}.

    Botones de solo ícono, unidos por grupo (.table-actions-group) y con tooltip que indica
    la acción. Grupos: Agregar · Editar · Duplicar · Eliminar | acciones propias (slot) |
    estado y auditoría | importar y exportar | Más | Limpiar filtros. En pantallas medianas
    (< 1600 px) estado, auditoría e intercambio de datos pasan al menú "Más"
    (.table-actions-wide / .table-actions-compact en resources/css/app.css).
--}}
@props([
    'module',                 // Slug de permisos (ej. stock-outs).
    'modelClass',             // FQCN del modelo, para la auditoría.
    'selected' => [],
    'selectedCount' => null,  // Total real con "seleccionar todos los que coinciden" (HasRowSelection::selectedCount()).
    'singleActive' => false,  // Un único registro seleccionado y no eliminado.
    'createEvent' => null,
    'editEvent' => null,
    'editParam' => 'id',
    'duplicable' => false,    // Requiere el trait DuplicatesRows en el componente.
    'singleDuplicable' => null, // Habilita Duplicar; por defecto igual que singleActive.
    'toggle' => true,
    'resettable' => true,
    'exportable' => true,
    'importable' => false,    // Requiere el trait ImportsRows en el componente.
    'importErrors' => [],
    'importKeyLabel' => null,
])

@php
    $user = auth()->user();
    $noSelection = count($selected) === 0;
    $auditId = $selected[0] ?? 'null';
    $auditDisabled = count($selected) !== 1;
    // El menú "Más" solo aparece si contiene al menos una acción permitida.
    $hasMore = $exportable
        || ($toggle && $user?->can("{$module}-toggle"))
        || $user?->can("{$module}-reverse")
        || $user?->can("{$module}-read")
        || ($importable && $user?->can("{$module}-import"));
@endphp

<div class="table-actions">
    {{-- Acción principal, edición y eliminación. --}}
    <div class="table-actions-group">
        @if ($createEvent)
            @can("{$module}-create")
                <x-tooltip text="Agregar">
                    <button type="button" wire:click="$dispatch('{{ $createEvent }}')" class="btn btn-primary btn-sm btn-table-icon" aria-label="Agregar">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </x-tooltip>
            @endcan
        @endif
        @if ($editEvent)
            @can("{$module}-update")
                <x-tooltip text="Editar seleccionado">
                    <button type="button" wire:click="editSelected('{{ $editEvent }}', '{{ $editParam }}')"
                            @disabled(! $singleActive) class="btn btn-sm btn-outline-primary btn-table-icon" aria-label="Editar seleccionado">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </x-tooltip>
            @endcan
        @endif
        @if ($duplicable)
            @can("{$module}-create")
                <x-tooltip text="Duplicar seleccionado">
                    <button type="button" wire:click="duplicateSelected" wire:loading.attr="disabled" wire:target="duplicateSelected"
                            @disabled(! ($singleDuplicable ?? $singleActive)) class="btn btn-sm btn-outline-primary btn-table-icon" aria-label="Duplicar seleccionado">
                        <i class="bi bi-copy"></i>
                    </button>
                </x-tooltip>
            @endcan
        @endif
        @can("{$module}-delete")
            <x-tooltip text="Eliminar seleccionados">
                <button type="button" x-on:click="confirmAction({ title: 'Eliminar', text: '¿Eliminar {{ $selectedCount ?? count($selected) }} registro(s)?' }).then(() => $wire.deleteSelected()).catch(() => {})"
                        @disabled($noSelection) class="btn btn-sm btn-outline-danger btn-table-icon" aria-label="Eliminar seleccionados">
                    <i class="bi bi-trash3"></i>
                </button>
            </x-tooltip>
        @endcan
    </div>

    {{-- Acciones propias del listado. --}}
    @if ($slot->hasActualContent())
        <div class="table-actions-group">{{ $slot }}</div>
    @endif

    <div class="table-actions-end">
        {{-- Estado y administración (pantallas anchas). --}}
        <div class="table-actions-group table-actions-wide">
            @if ($toggle)
                @can("{$module}-toggle")
                    <x-tooltip text="Activar / inactivar seleccionados">
                        <button type="button" wire:click="toggleSelected" wire:loading.attr="disabled" wire:target="toggleSelected" @disabled($noSelection)
                                class="btn btn-sm btn-outline-warning btn-table-icon" aria-label="Activar / inactivar seleccionados"><i class="bi bi-toggle2-on"></i></button>
                    </x-tooltip>
                @endcan
            @endif
            @can("{$module}-reverse")
                <x-tooltip text="Restaurar eliminados seleccionados">
                    <button type="button" wire:click="restoreSelected" wire:loading.attr="disabled" wire:target="restoreSelected" @disabled($noSelection)
                            class="btn btn-sm btn-outline-secondary btn-table-icon" aria-label="Restaurar eliminados seleccionados"><i class="bi bi-arrow-counterclockwise"></i></button>
                </x-tooltip>
            @endcan
            @can("{$module}-read")
                {{-- La auditoría aplica también a registros eliminados. --}}
                <x-tooltip text="Auditoría (seleccione un registro)">
                    <button type="button" wire:click="$dispatch('open-userstamps-audit', { modelClass: '{{ addslashes($modelClass) }}', modelId: {{ $auditId }} })"
                            @disabled($auditDisabled) class="btn btn-sm btn-outline-info btn-table-icon" aria-label="Auditoría"><i class="bi bi-clock-history"></i></button>
                </x-tooltip>
            @endcan
        </div>

        {{-- Intercambio de datos (pantallas anchas). --}}
        <div class="table-actions-group table-actions-wide">
            @if ($importable)
                @can("{$module}-import")
                    <x-tooltip text="Importar desde Excel / CSV">
                        <button type="button" wire:click="openImport" class="btn btn-sm btn-outline-primary btn-table-icon" aria-label="Importar desde Excel / CSV"><i class="bi bi-upload"></i></button>
                    </x-tooltip>
                @endcan
            @endif
            @if ($exportable)
                <x-tooltip text="Exportar a Excel">
                    <button type="button" wire:click="export('xlsx')" class="btn btn-sm btn-outline-success btn-table-icon" aria-label="Exportar a Excel"><i class="bi bi-file-earmark-excel"></i></button>
                </x-tooltip>
                <x-tooltip text="Exportar a CSV">
                    <button type="button" wire:click="export('csv')" class="btn btn-sm btn-outline-secondary btn-table-icon" aria-label="Exportar a CSV"><i class="bi bi-filetype-csv"></i></button>
                </x-tooltip>
            @endif
        </div>

        {{-- Menú "Más" (pantallas medianas y pequeñas): mismas acciones, permisos y condiciones. --}}
        @if ($hasMore)
            <div class="dropdown table-actions-compact" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                <x-tooltip text="Más acciones">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-table-icon" x-on:click="open = ! open"
                            :aria-expanded="open" aria-haspopup="true" aria-label="Más acciones"><i class="bi bi-three-dots"></i></button>
                </x-tooltip>
                <div class="dropdown-menu dropdown-menu-end table-actions-menu" :class="{ show: open }" x-on:click="open = false">
                    @if ($toggle)
                        @can("{$module}-toggle")
                            <button type="button" class="dropdown-item" wire:click="toggleSelected" @disabled($noSelection)>
                                <i class="bi bi-toggle2-on text-warning"></i> Activar / inactivar seleccionados
                            </button>
                        @endcan
                    @endif
                    @can("{$module}-reverse")
                        <button type="button" class="dropdown-item" wire:click="restoreSelected" @disabled($noSelection)>
                            <i class="bi bi-arrow-counterclockwise"></i> Restaurar eliminados seleccionados
                        </button>
                    @endcan
                    @can("{$module}-read")
                        <button type="button" class="dropdown-item" @disabled($auditDisabled)
                                wire:click="$dispatch('open-userstamps-audit', { modelClass: '{{ addslashes($modelClass) }}', modelId: {{ $auditId }} })">
                            <i class="bi bi-clock-history text-info"></i> Auditoría
                        </button>
                    @endcan
                    @if ($importable || $exportable)
                        <div class="dropdown-divider"></div>
                    @endif
                    @if ($importable)
                        @can("{$module}-import")
                            <button type="button" class="dropdown-item" wire:click="openImport">
                                <i class="bi bi-upload text-primary"></i> Importar desde Excel / CSV
                            </button>
                        @endcan
                    @endif
                    @if ($exportable)
                        <button type="button" class="dropdown-item" wire:click="export('xlsx')">
                            <i class="bi bi-file-earmark-excel text-success"></i> Exportar a Excel
                        </button>
                        <button type="button" class="dropdown-item" wire:click="export('csv')">
                            <i class="bi bi-filetype-csv"></i> Exportar a CSV
                        </button>
                    @endif
                </div>
            </div>
        @endif

        {{-- Acción secundaria. --}}
        @if ($resettable)
            <x-tooltip text="Limpiar filtros">
                <button wire:click="resetFilters" type="button" class="btn btn-sm btn-outline-secondary btn-table-icon" aria-label="Limpiar filtros"><i class="bi bi-eraser"></i></button>
            </x-tooltip>
        @endif
    </div>

    {{-- Modal de importación: fuera de los grupos ocultables para que siempre pueda abrirse. --}}
    @if ($importable)
        <x-table.import :module="$module" :errors-list="$importErrors" :key-label="$importKeyLabel" :button="false" />
    @endif
</div>
