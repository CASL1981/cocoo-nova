<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasToggleableStatus;
use App\Enums\GeographicLevel;
use App\Enums\Status;
use Database\Factories\GeographicLocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Exists;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Ubicación geográfica (país, departamento o ciudad) en una sola tabla jerárquica.
 *
 * La mantienen los usuarios. Una ubicación inactiva no se ofrece en los formularios nuevos, pero
 * los registros que ya la usan la siguen mostrando. Si está en uso o tiene hijos no se elimina ni
 * se cambian su código, nivel o padre (structureLockReason()).
 *
 * @property int $id
 * @property GeographicLevel $level
 * @property int|null $parent_id
 * @property string $code
 * @property string $name
 * @property int|null $population
 * @property Status $status
 * @property-read GeographicLocation|null $parent
 */
final class GeographicLocation extends Model implements HasToggleableStatus
{
    /** @use HasFactory<GeographicLocationFactory> */
    use HasFactory, SoftDeletes, Userstamps;

    public const COLOMBIA_CODE = 'CO';

    /** Columnas de proveedores que apuntan a esta tabla, por tabla. */
    private const REFERENCES = [
        'providers' => ['municipality_id'],
        'provider_versions' => ['country_id', 'department_id', 'municipality_id', 'nationality_country_id', 'birth_department_id', 'birth_municipality_id'],
    ];

    protected $fillable = ['level', 'parent_id', 'code', 'name', 'population', 'status'];

    protected function casts(): array
    {
        return [
            'level' => GeographicLevel::class,
            'status' => Status::class,
            'population' => 'integer',
        ];
    }

    /** @return BelongsTo<GeographicLocation, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<GeographicLocation, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function toggleStatus(): void
    {
        $this->status = $this->status === Status::Active ? Status::Inactive : Status::Active;
        $this->save();
    }

    /**
     * Búsqueda libre por código o nombre, propio o de su padre.
     *
     * @param  Builder<GeographicLocation>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $query->where(fn (Builder $inner) => $inner
            ->where('code', 'like', "%{$term}%")
            ->orWhere('name', 'like', "%{$term}%")
            ->orWhereHas('parent', fn (Builder $parent) => $parent->withoutGlobalScope(SoftDeletingScope::class)->where('name', 'like', "%{$term}%")));
    }

    /** Ruta legible hacia arriba, ej. "Córdoba · Colombia" para Montería. */
    public function parentPath(): string
    {
        $names = [];
        $node = $this;

        // Se detiene en el país (sin padre): no consulta más allá de lo cargado con with('parent.parent').
        while ($node->parent_id !== null && ($node = $node->parent) !== null) {
            $names[] = $node->name;
        }

        return implode(' · ', $names);
    }

    /**
     * Opciones activas [id => nombre] de todo un nivel (ej. países) para los selects.
     * $selectedId conserva la opción ya guardada aunque se haya inactivado.
     *
     * @return array<int, string>
     */
    public static function options(GeographicLevel $level, int|string|null $selectedId = null): array
    {
        return self::optionsQuery($level, $selectedId)->pluck('name', 'id')->all();
    }

    /**
     * Opciones activas [id => nombre] hijas de $parentId (ej. ciudades de un departamento);
     * vacío mientras no se elija el padre.
     *
     * @return array<int, string>
     */
    public static function childOptions(GeographicLevel $level, int|string|null $parentId, int|string|null $selectedId = null): array
    {
        if ($parentId === null || $parentId === '') {
            return [];
        }

        return self::optionsQuery($level, $selectedId)->where('parent_id', (int) $parentId)->pluck('name', 'id')->all();
    }

    /** @return Builder<GeographicLocation> */
    private static function optionsQuery(GeographicLevel $level, int|string|null $selectedId): Builder
    {
        return self::query()
            ->where('level', $level)
            ->where(fn (Builder $query) => $query->where('status', Status::Active)
                ->when($selectedId !== null && $selectedId !== '', fn (Builder $inner) => $inner->orWhere('id', (int) $selectedId)))
            ->orderBy('name');
    }

    /** Id del país Colombia, país por defecto del FO-300-01. */
    public static function colombiaId(): ?int
    {
        $id = self::query()->where('level', GeographicLevel::Country)->where('code', self::COLOMBIA_CODE)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Regla "existe y es del nivel indicado" (opcionalmente hija de $parentId), sin eliminadas.
     */
    public static function existsRule(GeographicLevel $level, int|string|null $parentId = null, bool $withParent = false): Exists
    {
        $rule = (new Exists(self::class, 'id'))->where('level', $level->value)->whereNull('deleted_at');

        return $withParent ? $rule->where('parent_id', (int) $parentId) : $rule;
    }

    /** Algún proveedor o formato FO-300-01 la usa. */
    public function isInUse(): bool
    {
        if (! $this->exists) {
            return false;
        }

        foreach (self::REFERENCES as $table => $columns) {
            $query = $this->getConnection()->table($table)->where(function ($query) use ($columns): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, $this->id);
                }
            });

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    /** Tiene ubicaciones hijas (incluidas las eliminadas, que siguen apuntando a ella). */
    public function hasChildren(): bool
    {
        return $this->exists && $this->children()->withTrashed()->exists();
    }

    /**
     * Motivo por el que no se pueden cambiar código, nivel ni padre; null si se pueden cambiar.
     */
    public function structureLockReason(): ?string
    {
        return match (true) {
            $this->isInUse() => 'ya está en uso en proveedores',
            $this->hasChildren() => 'tiene ubicaciones que le pertenecen',
            default => null,
        };
    }

    /**
     * Valida que $changes (level, parent_id, code) no modifiquen la estructura de una ubicación
     * bloqueada. Devuelve [campo => mensaje] con los cambios no permitidos.
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, string>
     */
    public function lockedChanges(array $changes): array
    {
        if (! $this->exists) {
            return [];
        }

        $changed = array_filter([
            'level' => array_key_exists('level', $changes) && $this->level->value !== (string) ($changes['level'] instanceof GeographicLevel ? $changes['level']->value : $changes['level']),
            'parent_id' => array_key_exists('parent_id', $changes) && $this->parent_id !== ($changes['parent_id'] === null ? null : (int) $changes['parent_id']),
            'code' => array_key_exists('code', $changes) && $this->code !== (string) $changes['code'],
        ]);

        if ($changed === [] || ($reason = $this->structureLockReason()) === null) {
            return [];
        }

        $labels = ['level' => 'el nivel', 'parent_id' => 'la ubicación a la que pertenece', 'code' => 'el código'];

        return array_map(fn (string $field) => "No se puede cambiar {$labels[$field]}: la ubicación {$reason}.", array_combine(array_keys($changed), array_keys($changed)));
    }

    /** No se elimina si está en uso o si tiene hijas activas (sin eliminar). */
    public function isDeletable(): bool
    {
        return ! $this->isInUse() && $this->children()->doesntExist();
    }
}
