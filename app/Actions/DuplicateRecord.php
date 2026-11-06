<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Duplicable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Crea una copia directa de un registro (botón "Duplicar" de los listados).
 *
 * La copia no hereda la auditoría ni la eliminación del original: Userstamps
 * registra como creador a quien duplica. El detalle (relaciones hijas) nunca se copia.
 * Las reglas propias de cada modelo viven en Duplicable::prepareDuplicate().
 */
final class DuplicateRecord
{
    /** Columnas que la copia no hereda (replicate() ya excluye id, created_at y updated_at). */
    private const EXCLUDED_COLUMNS = ['created_by', 'updated_by', 'deleted_by', 'deleted_at'];

    /**
     * @template TModel of Model&Duplicable
     *
     * @param  TModel  $source
     * @return TModel
     */
    public function execute(Model&Duplicable $source, User $actor): Model
    {
        $copy = $source->replicate(self::EXCLUDED_COLUMNS);
        $copy->prepareDuplicate($actor);
        $copy->save();

        return $copy;
    }
}
