<?php

namespace App\Livewire\Concerns;

trait SanitizesPayload
{
    /**
     * Convierte cadenas vacías en null y descarta claves opcionales (como password)
     * cuando vienen vacías, para no pisar un valor existente al editar.
     */
    protected function sanitize(array $data, array $dropIfEmpty = []): array
    {
        return collect($data)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->reject(fn ($value, $key) => in_array($key, $dropIfEmpty, true) && $value === null)
            ->all();
    }
}
