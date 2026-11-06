<?php

declare(strict_types=1);

namespace App\Actions\GeographicLocations;

use App\Enums\GeographicLevel;
use App\Models\GeographicLocation;
use Illuminate\Validation\ValidationException;

/**
 * Punto único de escritura de ubicaciones geográficas (pantalla e importación).
 *
 * Reglas: un país no tiene padre; un departamento pertenece a un país y una ciudad a un
 * departamento. Una ubicación en uso o con hijas no cambia su código, nivel ni padre.
 */
final class SaveGeographicLocation
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?GeographicLocation $location = null): GeographicLocation
    {
        $location ??= new GeographicLocation;

        if (($locked = $location->lockedChanges($data)) !== []) {
            throw ValidationException::withMessages($locked);
        }

        $level = isset($data['level']) ? GeographicLevel::from($data['level'] instanceof GeographicLevel ? $data['level']->value : (string) $data['level']) : $location->level;
        $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $location->parent_id;

        if (($error = self::hierarchyError($level, $parentId === null ? null : (int) $parentId, $location->id)) !== null) {
            throw ValidationException::withMessages(['parent_id' => $error]);
        }

        if ($level->parentLevel() === null) {
            $data['parent_id'] = null;
        }

        $location->fill($data)->save();

        return $location;
    }

    /**
     * Error de jerarquía para un nivel y un padre dados; null si son coherentes.
     */
    public static function hierarchyError(?GeographicLevel $level, ?int $parentId, ?int $selfId = null): ?string
    {
        if ($level === null) {
            return 'Indique el nivel de la ubicación.';
        }

        $expected = $level->parentLevel();

        if ($expected === null) {
            return null;
        }

        if ($parentId === null) {
            return ucfirst($level->withArticle())." debe pertenecer a {$expected->withArticle()}.";
        }

        $parent = GeographicLocation::query()->find($parentId);

        return match (true) {
            $parent === null || $parentId === $selfId => 'La ubicación a la que pertenece no existe o está eliminada.',
            $parent->level !== $expected => ucfirst($level->withArticle())." debe pertenecer a {$expected->withArticle()}.",
            default => null,
        };
    }
}
