<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Características</h5>
            @can('characteristics-create')
                <button type="button" wire:click="$dispatch('open-characteristic-create')" class="btn btn-sm btn-primary">Agregar</button>
            @endcan
        </div>
        <div class="card-body">
            <div class="row mb-2 g-2">
                <div class="col-6">
                    <input type="search" wire:model.live.debounce.300ms="filterName" class="form-control form-control-sm" placeholder="Buscar...">
                </div>
                <div class="col-6 text-end">
                    @can('characteristics-update')
                        <button type="button" wire:click="editSelected('open-characteristic-edit', 'characteristicId')"
                                @disabled(count($selected) !== 1) class="btn btn-sm btn-outline-primary">Editar</button>
                    @endcan
                    @can('characteristics-create')
                        <x-tooltip text="Duplicar seleccionado">
                            <button type="button" wire:click="duplicateSelected" wire:loading.attr="disabled" @disabled(! $this->singleActiveSelected())
                                    class="btn btn-sm btn-outline-primary" aria-label="Duplicar seleccionado"><i class="bi bi-copy"></i></button>
                        </x-tooltip>
                    @endcan
                    @can('characteristics-delete')
                        <button type="button" x-on:click="confirmAction({ title: 'Eliminar' }).then(() => $wire.deleteSelected()).catch(() => {})"
                                @disabled(count($selected) === 0) class="btn btn-sm btn-danger">Eliminar</button>
                    @endcan
                    @can('characteristics-reverse')
                        <x-tooltip text="Restaurar eliminados seleccionados">
                            <button aria-label="Restaurar eliminados seleccionados" type="button" wire:click="restoreSelected" @disabled(count($selected) === 0)
                                    class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></button>
                        </x-tooltip>
                    @endcan
                    @can('characteristics-read')
                        <x-tooltip text="Auditoría (seleccione un registro)">
                            <button aria-label="Auditoría (seleccione un registro)" type="button" wire:click="$dispatch('open-userstamps-audit', { modelClass: 'App\\Models\\Characteristic', modelId: {{ $selected[0] ?? 'null' }} })"
                                    @disabled(count($selected) !== 1) class="btn btn-sm btn-outline-info"><i class="bi bi-clock-history"></i></button>
                        </x-tooltip>
                    @endcan
                    <x-tooltip text="Exportar a Excel">
                        <button aria-label="Exportar a Excel" type="button" wire:click="export('xlsx')" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i></button>
                    </x-tooltip>
                    <x-tooltip text="Exportar a CSV">
                        <button aria-label="Exportar a CSV" type="button" wire:click="export('csv')" class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-csv"></i></button>
                    </x-tooltip>
                </div>
            </div>

            <table class="table table-sm table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 2rem;"></th>
                        <th role="button" wire:click="sort('name')">Nombre</th>
                        <th role="button" wire:click="sort('code')">Código</th>
                        <th role="button" wire:click="sort('status')">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->characteristics as $characteristic)
                        <tr wire:key="char-{{ $characteristic->id }}"
                            class="{{ $this->rowStatusClass($characteristic) }} {{ $activeCharacteristicId === $characteristic->id ? 'table-active' : '' }}"
                            role="button" wire:click="selectCharacteristic({{ $characteristic->id }})">
                            <td wire:click.stop><input type="checkbox" class="form-check-input" wire:model.live="selected" value="{{ $characteristic->id }}"></td>
                            <td>{{ $characteristic->name }}</td>
                            <td><code>{{ $characteristic->code }}</code></td>
                            <td><span class="badge {{ $characteristic->status->badgeClass() }}">{{ $characteristic->status->label() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <x-table.pagination :paginator="$this->characteristics" />
        </div>
    </div>

    <livewire:characteristics.characteristic-form-modal />
</div>
