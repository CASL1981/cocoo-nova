<?php

namespace App\Actions\Characteristics;

use App\Models\CharacteristicOption;

final class SaveCharacteristicOption
{
    /** @param array<string, mixed> $data */
    public function execute(array $data, ?CharacteristicOption $option = null): CharacteristicOption
    {
        $option ??= new CharacteristicOption;

        $option->fill($data)->save();

        return $option;
    }
}
