<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\UserStatus;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\Process;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Formulario de creación y edición de procesos.
 * El estado se gestiona con la acción de activar/inactivar del listado.
 */
final class ProcessForm extends Form
{
    use SanitizesPayload;

    // Proceso en edición; null al crear.
    public ?Process $process = null;

    public string $name = '';

    public ?string $director_id = null;

    public function setProcess(Process $process): void
    {
        $this->process = $process;
        $this->name = $process->name;
        $this->director_id = $process->director_id ? (string) $process->director_id : null;
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $this->director_id = $this->director_id === '' ? null : $this->director_id;

        return [
            'name' => ['required', 'string', 'min:3', 'max:100', Rule::unique(Process::class, 'name')->ignore($this->process)],
            // El director debe ser un usuario activo y no eliminado.
            'director_id' => ['nullable', 'integer', Rule::exists('users', 'id')
                ->where('status', UserStatus::Active->value)->whereNull('deleted_at')],
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }
}
