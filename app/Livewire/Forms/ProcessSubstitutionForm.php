<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\Status;
use App\Enums\UserStatus;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\Process;
use App\Models\ProcessSubstitution;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Formulario de reemplazos de directores de proceso.
 *
 * Reglas: el reemplazo es un usuario activo distinto del director titular y no puede
 * tener dos reemplazos con fechas cruzadas para el mismo proceso. Sin fecha de fin
 * es una asignación permanente.
 */
final class ProcessSubstitutionForm extends Form
{
    use SanitizesPayload;

    // Reemplazo en edición; null al crear.
    public ?ProcessSubstitution $substitution = null;

    public string $process_id = '';

    public string $substitute_id = '';

    public string $starts_on = '';

    public ?string $ends_on = null;

    public ?string $observations = null;

    public function setSubstitution(ProcessSubstitution $substitution): void
    {
        $this->substitution = $substitution;
        $this->process_id = (string) $substitution->process_id;
        $this->substitute_id = (string) $substitution->substitute_id;
        $this->starts_on = $substitution->starts_on->format('Y-m-d');
        $this->ends_on = $substitution->ends_on?->format('Y-m-d');
        $this->observations = $substitution->observations;
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $this->ends_on = $this->ends_on === '' ? null : $this->ends_on;

        return [
            'process_id' => ['required', 'integer', Rule::exists('processes', 'id')
                ->where('status', Status::Active->value)->whereNull('deleted_at')],
            'substitute_id' => ['required', 'integer', Rule::exists('users', 'id')
                ->where('status', UserStatus::Active->value)->whereNull('deleted_at'), $this->notTheDirectorRule()],
            'starts_on' => ['required', 'date', $this->noOverlapRule()],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'observations' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'ends_on.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
        ];
    }

    /** El director titular no puede ser su propio reemplazo. */
    private function notTheDirectorRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $directorId = Process::whereKey($this->process_id)->value('director_id');

            if ($directorId !== null && (int) $directorId === (int) $value) {
                $fail('El reemplazo debe ser una persona distinta al director del proceso.');
            }
        };
    }

    /** Un mismo usuario no puede tener dos reemplazos cruzados en el mismo proceso. */
    private function noOverlapRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->process_id === '' || $this->substitute_id === '' || strtotime((string) $value) === false) {
                return;
            }

            $endsOn = $this->ends_on;

            $overlaps = ProcessSubstitution::query()
                ->where('process_id', $this->process_id)
                ->where('substitute_id', $this->substitute_id)
                ->when($this->substitution, fn (Builder $q) => $q->whereKeyNot($this->substitution->getKey()))
                // Cruce de rangos: inicio existente <= fin nuevo y fin existente >= inicio nuevo (null = sin fin).
                ->when($endsOn, fn (Builder $q) => $q->whereDate('starts_on', '<=', $endsOn))
                ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $value))
                ->exists();

            if ($overlaps) {
                $fail('Este usuario ya tiene un reemplazo del mismo proceso que se cruza con estas fechas.');
            }
        };
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }
}
