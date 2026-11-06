{{-- La clave cambia con la selección para evitar la desincronización del checkbox (lección #8). --}}
@props(['id', 'selected' => []])

<td>
    <input type="checkbox" class="form-check-input" wire:model.live="selected" value="{{ $id }}"
           wire:key="row-check-{{ $id }}-{{ in_array((string) $id, $selected, true) ? '1' : '0' }}">
</td>
