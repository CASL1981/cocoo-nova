<div>
    <x-modal wire-model="show" :title="$role ? ($readOnly ? 'Permisos de '.$role->name.'
    (solo lectura)' : 'Permisos de '.$role->name) : 'Permisos'" size="xl">
        <div class="modal-body">
            @if ($role)
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th class="permission-module-header">Módulo / Recurso</th>
                                @foreach ($actions as $action => $label)
                                    <th class="text-center permission-action-header">
                                        <i class="bi {{ $actionIcons[$action] ?? 'bi-circle' }}" aria-hidden="true"></i>
                                        <span>{{ $label }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($modules as $module => $label)
                                <tr>
                                    <td class="fw-semibold">
                                        <i class="bi {{ $moduleIcons[$module] ?? 'bi-grid-3x3-gap-fill' }}" aria-hidden="true"></i>
                                        <span>{{ $label }}</span>
                                    </td>
                                    @foreach ($actions as $action => $actionLabel)
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input permission-checkbox"
                                                   wire:model="matrix.{{ $module }}.{{ $action }}" @disabled($readOnly)>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <x-slot:footer>
            @if ($readOnly)
                <button type="button" class="btn btn-light" x-on:click="open = false">Cerrar</button>
            @else
                <button type="button" class="btn btn-light" x-on:click="open = false">Cancelar</button>
                <button type="button" wire:click="save" class="btn btn-primary">
                    Guardar <span wire:loading class="spinner-border spinner-border-sm ms-1"></span>
                </button>
            @endif
        </x-slot:footer>
    </x-modal>
</div>
