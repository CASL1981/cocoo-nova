{{-- Fila de filtros por columna, generada desde columns(). --}}
@props(['columns', 'withSelection' => true])

@if ($withSelection)<th></th>@endif
@foreach ($columns as $meta)
    <th>
        @if (($meta['filter'] ?? null) === null)
        @elseif (($meta['type'] ?? 'text') === 'select')
            <x-forms.select wire:model.live="{{ $meta['filter'] }}" :options="$meta['options']" placeholder="Todos" />
        @else
            <input type="text" wire:model.live.debounce.300ms="{{ $meta['filter'] }}" class="form-control form-control-sm">
        @endif
    </th>
@endforeach
