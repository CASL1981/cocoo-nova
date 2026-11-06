<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\GeographicLevel;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\GeographicLocation;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Formulario de creación y edición de una ubicación geográfica.
 * El estado no se edita aquí: se gestiona con la acción de activar/inactivar.
 */
final class GeographicLocationForm extends Form
{
    use SanitizesPayload;

    /** Formato del código: mayúsculas, números y guion (ej. CO, 23, 23001, AU-NSW). */
    public const CODE_REGEX = '/^[A-Z0-9-]+$/';

    // Ubicación en edición; null cuando se crea una nueva.
    public ?GeographicLocation $location = null;

    public string $level = '';

    public ?string $parent_id = null;

    public string $code = '';

    public string $name = '';

    public ?string $population = null;

    // Motivo por el que código, nivel y padre son de solo lectura (en uso o con hijas); null si se editan.
    public ?string $lockReason = null;

    public function setLocation(GeographicLocation $location): void
    {
        $this->location = $location;
        $this->lockReason = $location->structureLockReason();
        $this->level = $location->level->value;
        $this->parent_id = $location->parent_id === null ? null : (string) $location->parent_id;
        $this->code = $location->code;
        $this->name = $location->name;
        $this->population = $location->population === null ? null : (string) $location->population;
    }

    /** Nivel elegido, o null mientras no se elija. */
    public function levelEnum(): ?GeographicLevel
    {
        return GeographicLevel::tryFrom($this->level);
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $parentLevel = $this->levelEnum()?->parentLevel();

        return [
            'level' => ['required', Rule::enum(GeographicLevel::class)],
            'parent_id' => $parentLevel === null
                ? ['nullable']
                : ['required', 'integer', GeographicLocation::existsRule($parentLevel)],
            'code' => ['required', 'string', 'max:20', 'regex:'.self::CODE_REGEX, Rule::unique(GeographicLocation::class, 'code')->ignore($this->location)],
            'name' => ['required', 'string', 'max:150'],
            'population' => ['nullable', 'integer', 'min:0', 'max:9999999999'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'code.regex' => 'El código solo admite mayúsculas, números y guion (ej. CO, 23001, AU-NSW).',
            'code.unique' => 'Ya existe una ubicación con este código (puede estar eliminada).',
            'parent_id.required' => 'Elija la ubicación a la que pertenece.',
            'parent_id.exists' => 'La ubicación elegida no es del nivel correcto o está eliminada.',
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return ['level' => 'nivel', 'parent_id' => 'pertenece a', 'code' => 'código', 'name' => 'nombre', 'population' => 'población'];
    }

    /**
     * Datos validados y limpios listos para persistir. El código se guarda en mayúsculas.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $this->code = mb_strtoupper(trim($this->code));

        $data = $this->sanitize($this->validate());

        if ($this->levelEnum()?->parentLevel() === null) {
            $data['parent_id'] = null;
        }

        return $data;
    }
}
