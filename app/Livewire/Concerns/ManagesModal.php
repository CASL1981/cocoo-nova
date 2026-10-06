<?php

namespace App\Livewire\Concerns;

trait ManagesModal
{
    public bool $show = false;

    // Se ejecuta tanto si el cierre lo origina el cliente (Escape/clic afuera/botón X,
    // vía @entangle) como si lo origina el propio componente al terminar de guardar.
    public function updatedShow(bool $value): void
    {
        if (! $value) {
            $this->resetModalState();
        }
    }

    protected function openModal(): void
    {
        $this->resetModalState();
        $this->show = true;
    }

    protected function closeModal(): void
    {
        $this->show = false;
        $this->resetModalState();
    }

    // Cada modal define qué debe limpiar (su Form object, errores, etc.)
    abstract protected function resetModalState(): void;
}
