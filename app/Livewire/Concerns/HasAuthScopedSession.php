<?php

namespace App\Livewire\Concerns;

trait HasAuthScopedSession
{
    public int $authId = 0;

    // Livewire llama automáticamente a "boot{NombreDelTrait}" — no hace falta invocarlo a mano
    public function bootHasAuthScopedSession(): void
    {
        $this->authId = (int) auth()->id();
    }
}
