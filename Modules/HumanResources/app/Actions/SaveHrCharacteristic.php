<?php

declare(strict_types=1);

namespace Modules\HumanResources\App\Actions;

use Illuminate\Validation\ValidationException;
use Modules\HumanResources\App\Models\HrCharacteristic;

/**
 * Crea o actualiza una característica de Talento Humano. El código no cambia si ya tiene opciones.
 */
final class SaveHrCharacteristic
{
    /** @param array<string, mixed> $data */
    public function execute(array $data, ?HrCharacteristic $characteristic = null): HrCharacteristic
    {
        $characteristic ??= new HrCharacteristic;

        if (isset($data['code']) && $data['code'] !== $characteristic->code && $characteristic->isCodeLocked()) {
            throw ValidationException::withMessages(['code' => 'El código no se puede cambiar: la característica ya tiene opciones en uso.']);
        }

        $characteristic->fill($data)->save();

        return $characteristic;
    }
}
