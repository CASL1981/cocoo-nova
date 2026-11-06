<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\HumanResources\App\Models\HrCharacteristic;

/**
 * Talento Humano → Características: listas propias del área (ej. Cargos), maestro-detalle.
 */
new #[Layout('layouts.app')] #[Title('Características de Talento Humano')] class extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', HrCharacteristic::class);
    }
};
?>

<div>
    <h4 class="mb-3">Características de Talento Humano</h4>
    <div class="row g-3">
        <div class="col-xxl-5"><livewire:humanresources::characteristics.hr-characteristic-list /></div>
        <div class="col-xxl-7"><livewire:humanresources::characteristics.hr-characteristic-option-list /></div>
    </div>
</div>
