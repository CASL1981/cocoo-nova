<?php

declare(strict_types=1);

namespace App\Imports;

use App\Actions\GeographicLocations\SaveGeographicLocation;
use App\Contracts\RowImporter;
use App\Enums\GeographicLevel;
use App\Enums\Status;
use App\Livewire\Forms\GeographicLocationForm;
use App\Models\GeographicLocation;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Importación masiva de ubicaciones geográficas (Configuración → Ubicación geográfica).
 *
 * Clave: código. Si existe (aunque esté eliminada) se actualiza; si no, se crea. Una columna
 * ausente (o el código del padre vacío) no se toca: un archivo con solo "code" y "population" actualiza la población.
 * El padre se indica con su código (parent_code) y debe existir antes de importar (importe
 * primero los países y departamentos nuevos).
 */
final class GeographicLocationImporter implements DataAwareRule, RowImporter, ValidationRule
{
    /** @var array<string, mixed> Fila en validación (regla de coherencia de la fila). */
    private array $row = [];

    public function __construct(private readonly SaveGeographicLocation $saveLocation = new SaveGeographicLocation) {}

    public function modelClass(): string
    {
        return GeographicLocation::class;
    }

    public function columns(): array
    {
        return [
            'level' => 'Nivel',
            'code' => 'Código',
            'name' => 'Nombre',
            'parent_code' => 'Código del padre',
            'population' => 'Población',
            'status' => 'Estado',
        ];
    }

    public function key(): string
    {
        return 'code';
    }

    public function rules(): array
    {
        return [
            // La regla de la fila (esta misma clase) valida nivel, padre y bloqueo con todos los datos.
            'code' => ['required', 'string', 'max:20', 'regex:'.GeographicLocationForm::CODE_REGEX, $this],
            'level' => ['nullable', Rule::enum(GeographicLevel::class)],
            'name' => ['required', 'string', 'max:150'],
            'parent_code' => ['nullable', 'string', 'max:20'],
            'population' => ['nullable', 'integer', 'min:0', 'max:9999999999'],
            'status' => ['nullable', Rule::enum(Status::class)],
        ];
    }

    /** Nivel y estado por valor o etiqueta (sin distinguir mayúsculas); códigos en mayúsculas. */
    public function prepare(array $row): array
    {
        foreach (['level' => GeographicLevel::options(), 'status' => Status::options()] as $field => $options) {
            if (is_string($row[$field] ?? null)) {
                $byLabel = array_change_key_case(array_flip($options));
                $row[$field] = $byLabel[mb_strtolower($row[$field])] ?? mb_strtolower($row[$field]);
            }
        }

        foreach (['code', 'parent_code'] as $field) {
            if (is_string($row[$field] ?? null)) {
                $row[$field] = mb_strtoupper($row[$field]);
            }
        }

        return $row;
    }

    public function save(array $data, ?Model $existing): void
    {
        // Código del padre vacío: conserva el padre actual (en una nueva, la validación ya lo exigió).
        if (($data['parent_code'] ?? null) !== null) {
            $data['parent_id'] = $this->parentId($data['parent_code']);
        }

        unset($data['parent_code']);

        // Nivel o estado vacíos conservan el valor actual (una nueva nace activa).
        foreach (['level', 'status'] as $field) {
            if (($data[$field] ?? null) === null) {
                unset($data[$field]);
            }
        }

        $this->saveLocation->execute($data, $existing instanceof GeographicLocation ? $existing : null);
    }

    /** @param array<string, mixed> $data */
    public function setData(array $data): static
    {
        $this->row = $data;

        return $this;
    }

    /**
     * Coherencia de la fila: una nueva exige nivel y nombre; el padre debe ser del nivel
     * correcto; una ubicación en uso o con hijas no cambia nivel ni padre.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $existing = GeographicLocation::withTrashed()->where('code', (string) $value)->first();

        if ($existing === null && (($this->row['level'] ?? null) === null || ($this->row['name'] ?? null) === null)) {
            $fail('Una ubicación nueva requiere nivel y nombre.');

            return;
        }

        $level = GeographicLevel::tryFrom((string) ($this->row['level'] ?? '')) ?? $existing?->level;
        $parentGiven = ($this->row['parent_code'] ?? null) !== null;
        $parentId = $parentGiven ? $this->parentId($this->row['parent_code']) : $existing?->parent_id;

        if ($parentGiven && $parentId === null) {
            $fail("No existe la ubicación padre con código \"{$this->row['parent_code']}\".");

            return;
        }

        $changes = array_filter(['level' => $this->row['level'] ?? null, 'parent_id' => $parentGiven ? $parentId : null], fn ($v) => $v !== null);

        foreach ($existing?->lockedChanges($changes) ?? [] as $message) {
            $fail($message);

            return;
        }

        if (($error = SaveGeographicLocation::hierarchyError($level, $parentId, $existing?->id)) !== null) {
            $fail($error);
        }
    }

    private function parentId(mixed $code): ?int
    {
        if ($code === null || $code === '') {
            return null;
        }

        $id = GeographicLocation::query()->where('code', (string) $code)->value('id');

        return $id === null ? null : (int) $id;
    }
}
