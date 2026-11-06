{{--
    Campo de búsqueda con lista de resultados (alternativa a Select2 sin jQuery).

    El texto se enlaza a una propiedad Livewire ($search) con debounce; el componente
    que lo usa calcula los resultados en el servidor y recibe la opción elegida en el
    método $select. Así no hay DOM manipulado por terceros que choque con Livewire.
--}}
@props([
    'name',                   // Clave del error de validación (ej. form.product_id).
    'search',                 // Propiedad Livewire con el texto de búsqueda.
    'select',                 // Método Livewire que recibe el valor elegido.
    'results' => [],          // [valor => etiqueta]
    'label' => null,
    'placeholder' => 'Buscar...',
    'id' => null,
    'required' => false,      // Asterisco en la etiqueta (la validación es del servidor).
    'hint' => null,
])

@php $id = $id ?? str_replace('.', '-', $name); @endphp

<x-forms.field :id="$id" :name="$name" :label="$label" :required="$required" :hint="$hint"
               x-data="{ open: false }" x-on:click.outside="open = false" class="position-relative">
    <input id="{{ $id }}" type="text" autocomplete="off" placeholder="{{ $placeholder }}"
           wire:model.live.debounce.300ms="{{ $search }}"
           x-on:focus="open = true; $event.target.select()" x-on:click="open = true" x-on:input="open = true" x-on:keydown.escape="open = false"
           @if ($required) aria-required="true" @endif
           {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>

    @if (count($results) > 0)
        <div class="dropdown-menu w-100 shadow-sm overflow-auto" style="max-height: 16rem;" :class="{ 'show': open }">
            @foreach ($results as $value => $text)
                <button type="button" class="dropdown-item text-wrap" wire:key="{{ $id }}-option-{{ $value }}"
                        wire:click="{{ $select }}(@js((string) $value))" x-on:click="open = false">{{ $text }}</button>
            @endforeach
        </div>
    @endif

</x-forms.field>
