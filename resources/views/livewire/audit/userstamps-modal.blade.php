<div>
    <x-modal wire-model="show" title="Auditoría del registro" size="md">
        <div class="modal-body">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <strong>Creado por:</strong> {{ $createdByName ?? 'Sistema' }}
                    <br><small class="text-muted">{{ $createdAt ?? '-' }}</small>
                </li>
                <li class="list-group-item">
                    @if ($wasEdited)
                        <strong>Última edición por:</strong> {{ $updatedByName ?? 'Sistema' }}
                        <br><small class="text-muted">{{ $updatedAt }}</small>
                    @else
                        <strong>Edición:</strong> <span class="text-muted">Sin ediciones posteriores a la creación</span>
                    @endif
                </li>
                <li class="list-group-item">
                    @if ($wasDeleted)
                        <strong>Eliminado por:</strong> {{ $deletedByName ?? 'Sistema' }}
                        <br><small class="text-muted">{{ $deletedAt }}</small>
                    @else
                        <strong>Eliminación:</strong> <span class="text-muted">El registro está activo</span>
                    @endif
                </li>
            </ul>
        </div>
        <x-slot:footer>
            <button type="button" class="btn btn-light" x-on:click="open = false">Cerrar</button>
        </x-slot:footer>
    </x-modal>
</div>
