<?php

use App\Enums\ProviderScope;
use App\Livewire\Providers\ProviderIndex;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * Proveedores administrativos (línea de permisos administrative-providers): vinculaciones del proceso con los terceros.
 * La lógica y la vista son comunes a los dos procesos (App\Livewire\Providers\ProviderIndex).
 */
new #[Layout('layouts.app')] #[Title('Proveedores administrativos')] class extends ProviderIndex
{
    protected function scope(): ProviderScope
    {
        return ProviderScope::Administrative;
    }
};
?>

@include('livewire.providers.provider-index-body')
