<?php

namespace App\Actions\Characteristics;

use App\Models\Characteristic;
use Illuminate\Validation\ValidationException;

final class SaveCharacteristic
{
    /**
     * Crea o actualiza una característica. El código no cambia si ya tiene opciones (isCodeLocked).
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Characteristic $characteristic = null): Characteristic
    {
        $characteristic ??= new Characteristic;

        if (isset($data['code']) && $data['code'] !== $characteristic->code && $characteristic->isCodeLocked()) {
            throw ValidationException::withMessages(['code' => 'El código no se puede cambiar: la característica ya tiene opciones en uso.']);
        }

        $characteristic->fill($data)->save();

        return $characteristic;
    }
}
