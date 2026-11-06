{{-- Fila de la tabla de líneas cuando el documento aún no tiene registros. --}}
@props([
    'colspan',
    'text',
    'hint' => null,          // Indicación breve de cómo agregar líneas.
    'icon' => 'bi-inbox',
])

<tr>
    <td colspan="{{ $colspan }}" class="document-empty">
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
        <span class="document-empty-text">{{ $text }}</span>
        @if ($hint)
            <span class="document-empty-hint">{{ $hint }}</span>
        @endif
    </td>
</tr>
