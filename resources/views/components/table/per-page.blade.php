@props(['options' => [10, 25, 50, 100]])

<label class="table-per-page">
    <span>Mostrar</span>
    <select wire:model.live="perPage" class="form-select form-select-sm" aria-label="Registros por página">
        @foreach ($options as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach
    </select>
    <span>registros</span>
</label>
