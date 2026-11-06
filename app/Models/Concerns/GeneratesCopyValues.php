<?php

namespace App\Models\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Valores únicos para la copia de un registro (ver App\Contracts\Duplicable).
 *
 * Requiere SoftDeletes en el modelo: los registros eliminados siguen ocupando
 * el valor único, por eso se consultan con withTrashed().
 */
trait GeneratesCopyValues
{
    /**
     * Devuelve el valor de la columna con el sufijo de copia: "X-COPIA" y, si ya
     * existe, "X-COPIA-2", "X-COPIA-3"... (el separador del número es el primer
     * carácter del sufijo, p. ej. "_copia_2"). Recorta el valor original para
     * respetar el largo máximo de la columna.
     *
     * @param  (Closure(Builder<self>): mixed)|null  $scope  Acota la unicidad (p. ej. por característica).
     */
    protected function uniqueCopyValue(string $column, int $maxLength, string $suffix = '-COPIA', ?Closure $scope = null): string
    {
        $base = (string) $this->getAttribute($column);

        for ($attempt = 1; ; $attempt++) {
            $tail = $attempt === 1 ? $suffix : $suffix.$suffix[0].$attempt;
            $candidate = mb_substr($base, 0, $maxLength - mb_strlen($tail)).$tail;

            $query = static::withTrashed()->where($column, $candidate);

            if ($scope) {
                $scope($query);
            }

            if (! $query->exists()) {
                return $candidate;
            }
        }
    }
}
