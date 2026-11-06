<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

/**
 * Opciones [valor => etiqueta] de un enum respaldado que define label(), para selects y filtros.
 */
trait HasLabelOptions
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }

    /**
     * Opciones [valor => etiqueta] de un subconjunto de casos.
     *
     * @param  list<self>  $cases
     * @return array<string, string>
     */
    public static function optionsFor(array $cases): array
    {
        return collect($cases)->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
