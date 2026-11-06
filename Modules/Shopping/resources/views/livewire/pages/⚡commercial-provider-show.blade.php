<?php

use App\Enums\ProviderScope;
use App\Livewire\Providers\ProviderShow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * Detalle de un proveedor desde la vinculación commercial (formato FO-300-01, revisión y anexos).
 * La lógica y la vista son comunes a los dos procesos (App\Livewire\Providers\ProviderShow).
 */
new #[Layout('layouts.app')] #[Title('Detalle del proveedor')] class extends ProviderShow
{
    protected function scope(): ProviderScope
    {
        return ProviderScope::Commercial;
    }
};
?>

@include('livewire.providers.provider-show-body')
