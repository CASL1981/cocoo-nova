<?php

namespace App\Livewire\Concerns;

trait Notifies
{
    protected function toastSuccess(string $message): void
    {
        $this->dispatch('toast', type: 'success', message: $message);
    }

    protected function toastError(string $message): void
    {
        $this->dispatch('toast', type: 'error', message: $message);
    }
}
