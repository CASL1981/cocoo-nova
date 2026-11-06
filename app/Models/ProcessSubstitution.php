<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\ProcessSubstitutionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Reemplazo del director de un proceso (vacaciones, licencias o asignación permanente).
 *
 * Reglas de negocio: lo registra el Administrador; durante la vigencia el reemplazo
 * aprueba junto con el director titular, que conserva la aprobación. Sin fecha de fin
 * es una asignación permanente (ej. Financiero aprueba las requisiciones de Gerencia).
 *
 * @property int $id
 * @property int $process_id
 * @property int $substitute_id
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable|null $ends_on
 * @property string|null $observations
 */
final class ProcessSubstitution extends Model
{
    /** @use HasFactory<ProcessSubstitutionFactory> */
    use HasFactory;

    use SoftDeletes;
    use Userstamps;

    protected $fillable = ['process_id', 'substitute_id', 'starts_on', 'ends_on', 'observations'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /** @return BelongsTo<Process, $this> */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /** @return BelongsTo<User, $this> */
    public function substitute(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substitute_id');
    }

    /**
     * Reemplazos vigentes en una fecha (por defecto hoy); sin fecha de fin no vencen.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActiveOn(Builder $query, ?CarbonInterface $date = null): Builder
    {
        $day = ($date ?? now())->toDateString();

        return $query->whereDate('starts_on', '<=', $day)
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $day));
    }

    /** Indica si el reemplazo es permanente (sin fecha de fin). */
    public function isPermanent(): bool
    {
        return $this->ends_on === null;
    }
}
