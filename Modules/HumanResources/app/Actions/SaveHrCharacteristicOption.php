<?php

declare(strict_types=1);

namespace Modules\HumanResources\App\Actions;

use Illuminate\Validation\ValidationException;
use Modules\HumanResources\App\Models\HrCharacteristic;
use Modules\HumanResources\App\Models\HrCharacteristicOption;

/**
 * Crea o actualiza una opción de Talento Humano.
 *
 * Proceso y jefe inmediato solo aplican a Cargos (en otras características se guardan vacíos).
 * El jefe inmediato es otro cargo de la misma característica y no puede ser el propio cargo
 * ni uno de sus subordinados.
 */
final class SaveHrCharacteristicOption
{
    /** @param array<string, mixed> $data */
    public function execute(array $data, ?HrCharacteristicOption $option = null): HrCharacteristicOption
    {
        $option ??= new HrCharacteristicOption;
        $characteristicId = (int) ($data['hr_characteristic_id'] ?? $option->hr_characteristic_id);
        $characteristic = HrCharacteristic::withTrashed()->findOrFail($characteristicId);

        if (! $characteristic->isPositions()) {
            $data['process_id'] = null;
            $data['parent_id'] = null;
        }

        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;

        if ($parentId !== null) {
            $parentValid = HrCharacteristicOption::query()
                ->whereKey($parentId)->where('hr_characteristic_id', $characteristicId)->exists();

            if (! $parentValid || $option->wouldCreateCycle($parentId)) {
                throw ValidationException::withMessages(['parent_id' => 'El jefe inmediato debe ser otro cargo y no puede ser uno de sus subordinados.']);
            }
        }

        $option->fill($data)->save();

        return $option;
    }
}
