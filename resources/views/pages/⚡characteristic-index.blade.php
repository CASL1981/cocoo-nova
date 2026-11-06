<?php

use App\Models\Characteristic;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Características')] class extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', Characteristic::class);
    }
};
?>

<div>
    <h4 class="mb-3">Características</h4>
    <div class="row g-3">
        <div class="col-md-6"><livewire:characteristics.characteristic-list /></div>
        <div class="col-md-6"><livewire:characteristics.characteristic-option-list /></div>
    </div>
</div>
