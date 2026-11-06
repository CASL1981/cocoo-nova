<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Opciones</h5>
            @can('characteristics-create')
                <button type="button" wire:click="$dispatch('open-characteristic-option-create', { characteristicId: {{ $characteristicId ?? 'null' }} })"
                        @disabled(! $characteristicId || $this->parentTrashed) class="btn btn-sm btn-primary">Agregar</button>

                {{-- Aviso al inicio del bloque @else, antes de la barra de botones --}}
                @if ($this->parentTrashed)
                    <div class="alert alert-warning py-2 small mb-2">
                        La característica está eliminada: restáurala para gestionar sus opciones.
                    </div>
                @endif
            @endcan
        </div>
        <div class="card-body">
            @if (! $characteristicId)
                <p class="text-muted text-center py-4">Selecciona una característica a la izquierda para ver sus opciones.</p>
            @else
                <div class="row mb-2 g-2">
                    <div class="col-6">
                        <input type="search" wire:model.live.debounce.300ms="filterName" class="form-control form-control-sm"
                               placeholder="Buscar opción..." aria-label="Buscar opción">
                    </div>
                    <div class="col-6 text-end">
                        @can('characteristics-update')
                            <button type="button" wire:click="editSelected('open-characteristic-option-edit', 'optionId')"
                                    @disabled($this->parentTrashed || ! $this->singleActiveSelected()) class="btn btn-sm btn-outline-primary">Editar</button>
                        @endcan
                        @can('characteristics-create')
                            <x-tooltip text="Duplicar seleccionado">
                                <button type="button" wire:click="duplicateSelected" wire:loading.attr="disabled" @disabled($this->parentTrashed || ! $this->singleActiveSelected())
                                        class="btn btn-sm btn-outline-primary" aria-label="Duplicar seleccionado"><i class="bi bi-copy"></i></button>
                            </x-tooltip>
                        @endcan
                        @can('characteristics-delete')
                            <button type="button" x-on:click="confirmAction({ title: 'Eliminar' }).then(() => $wire.deleteSelected()).catch(() => {})"
                                    @disabled($this->parentTrashed || count($selected) === 0) class="btn btn-sm btn-danger">Eliminar</button>
                        @endcan
                        @can('characteristics-reverse')
                            <x-tooltip text="Restaurar eliminados seleccionados">
                                <button type="button" wire:click="restoreSelected" @disabled($this->parentTrashed || count($selected) === 0)
                                        class="btn btn-sm btn-outline-secondary" aria-label="Restaurar eliminados seleccionados"><i class="bi bi-arrow-counterclockwise"></i></button>
                            </x-tooltip>
                        @endcan
                        @can('characteristics-read')
                            <x-tooltip text="Auditoría (seleccione un registro)">
                                <button type="button" wire:click="$dispatch('open-userstamps-audit', { modelClass: 'App\\Models\\CharacteristicOption', modelId: {{ $selected[0] ?? 'null' }} })"
                                        @disabled(count($selected) !== 1) class="btn btn-sm btn-outline-info" aria-label="Auditoría"><i class="bi bi-clock-history"></i></button>
                            </x-tooltip>
                        @endcan
                        <x-tooltip text="Exportar a Excel">
                            <button type="button" wire:click="export('xlsx')" class="btn btn-sm btn-outline-success" aria-label="Exportar a Excel"><i class="bi bi-file-earmark-excel"></i></button>
                        </x-tooltip>
                        <x-tooltip text="Exportar a CSV">
                            <button type="button" wire:click="export('csv')" class="btn btn-sm btn-outline-secondary" aria-label="Exportar a CSV"><i class="bi bi-filetype-csv"></i></button>
                        </x-tooltip>
                    </div>
                </div>

                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th style="width: 2rem;"></th>
                            <th role="button" wire:click="sort('name')">Detalle</th>
                            <th role="button" wire:click="sort('status')">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->options as $option)
                            <tr wire:key="opt-{{ $option->id }}" class="{{ $this->rowStatusClass($option) }}">
                                {{-- La clave cambia con el estado de selección para evitar la desincronización del checkbox. --}}
                                <td><input type="checkbox" class="form-check-input" wire:model.live="selected" value="{{ $option->id }}"
                                        wire:key="opt-check-{{ $option->id }}-{{ in_array((string) $option->id, $selected, true) ? '1' : '0' }}"></td>
                                <td>{{ $option->name }}</td>
                                <td><span class="badge {{ $option->status->badgeClass() }}">{{ $option->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">{{ $filterName === '' ? 'Sin opciones todavía' : 'Sin resultados para la búsqueda' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <x-table.pagination :paginator="$this->options" />
            @endif
        </div>
    </div>

    <livewire:characteristics.characteristic-option-form-modal />
</div>
