@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success mb-3']) }} role="alert">
        {{ $status }}
    </div>
@endif
