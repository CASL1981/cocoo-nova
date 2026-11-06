{{--
    Paginación compacta de Livewire (reemplaza livewire::bootstrap en toda la aplicación).

    Muestra solo: ‹ 1 … (actual − 1) actual (actual + 1) … última ›, para que el
    paginador quepa en paneles angostos y pantallas de portátil. No incluye el
    resumen "Mostrando X a Y de Z": lo pinta <x-table.pagination>.
--}}
@php
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
        JS
        : '';

    $pageName = $paginator->getPageName();
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    // Ventana: primera, vecinas de la actual y última; "…" donde hay saltos.
    $pages = collect([1, $current - 1, $current, $current + 1, $last])
        ->filter(fn (int $page) => $page >= 1 && $page <= $last)
        ->unique()->sort()->values();
@endphp

<div>
    @if ($paginator->hasPages())
        <nav aria-label="Paginación">
            <ul class="pagination pagination-sm mb-0 flex-wrap">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-hidden="true">&lsaquo;</span></li>
                @else
                    <li class="page-item">
                        <button type="button" class="page-link" wire:click="previousPage('{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:loading.attr="disabled" aria-label="Página anterior" x-tooltip="'Página anterior'">&lsaquo;</button>
                    </li>
                @endif

                @foreach ($pages as $index => $page)
                    @if ($index > 0 && $page - $pages[$index - 1] > 1)
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">…</span></li>
                    @endif

                    @if ($page === $current)
                        <li class="page-item active" wire:key="paginator-{{ $pageName }}-page-{{ $page }}" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item" wire:key="paginator-{{ $pageName }}-page-{{ $page }}">
                            <button type="button" class="page-link" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}">{{ $page }}</button>
                        </li>
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <button type="button" class="page-link" wire:click="nextPage('{{ $pageName }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                wire:loading.attr="disabled" aria-label="Página siguiente" x-tooltip="'Página siguiente'">&rsaquo;</button>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-hidden="true">&rsaquo;</span></li>
                @endif
            </ul>
        </nav>
    @endif
</div>
