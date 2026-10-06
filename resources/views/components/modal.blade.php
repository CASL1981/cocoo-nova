@props([
    'wireModel' => 'show',
    'title' => null,
    'size' => 'lg',   // sm | md | lg | xl
])

<div
    x-data="{ open: @entangle($wireModel) }"
    x-cloak
    x-show="open"
    x-on:keydown.escape.window="open = false"
    class="modal fade"
    :class="{ 'show d-block': open }"
    tabindex="-1"
    style="background-color: rgba(0,0,0,.55);"
>
    <div class="modal-dialog {{ $size !== 'md' ? 'modal-'.$size : '' }}" x-on:click.self="open = false">
        <div class="modal-content" @click.stop>
            @if ($title)
                <div class="modal-header">
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" x-on:click="open = false"></button>
                </div>
            @endif

            {{ $slot }}

            @isset($footer)
                <div class="modal-footer">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
