{{--
    Modal controlado por Livewire (wire-model) con Alpine.

    Encabezado con ícono y subtítulo opcionales; el cuerpo se desplaza y el pie queda
    fijo (modal-dialog-scrollable); en celulares ocupa toda la pantalla.
--}}
@props([
    'wireModel' => 'show',
    'title' => null,
    'subtitle' => null,  // Texto breve bajo el título.
    'icon' => null,      // Clase de Bootstrap Icons (ej. bi-box-seam).
    'size' => 'lg',      // sm | md | lg | xl
])

<div
    x-data="{ open: @entangle($wireModel) }"
    x-cloak
    x-show="open"
    x-on:keydown.escape.window="open = false"
    class="modal fade app-modal"
    :class="{ 'show d-block': open }"
    tabindex="-1"
    role="dialog"
    aria-modal="true"
>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down {{ $size !== 'md' ? 'modal-'.$size : '' }}"
         x-on:click.self="open = false">
        <div class="modal-content" @click.stop>
            @if ($title)
                <div class="modal-header">
                    <div class="app-modal-heading">
                        @if ($icon)
                            <span class="app-modal-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                        @endif
                        <div>
                            <h5 class="modal-title">{{ $title }}</h5>
                            @if ($subtitle)
                                <p class="app-modal-subtitle">{{ $subtitle }}</p>
                            @endif
                        </div>
                    </div>
                    <button type="button" class="btn-close" x-on:click="open = false" aria-label="Cerrar"></button>
                </div>
            @endif

            {{ $slot }}

            @isset($footer)
                <div class="modal-footer">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
