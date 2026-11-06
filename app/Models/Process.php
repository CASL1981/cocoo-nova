<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\HasToggleableStatus;
use App\Enums\Status;
use Database\Factories\ProcessFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * Proceso (área) de la organización: Gerencia, Administrativo, Financiero, Comercial
 * y Logística y Operaciones.
 *
 * Es transversal (vive en app/) porque lo usan Usuarios y, más adelante, el módulo
 * Administrativo para resolver quién aprueba cada requisición: el director del
 * proceso o sus reemplazos vigentes.
 *
 * @property int $id
 * @property string $name
 * @property int|null $director_id
 * @property Status $status
 */
final class Process extends Model implements HasToggleableStatus
{
    /** @use HasFactory<ProcessFactory> */
    use HasFactory;

    use SoftDeletes;
    use Userstamps;

    /** Gerencia: aprueba la vinculación de los proveedores PEP. */
    public const GENERAL_MANAGEMENT = 'Gerencia';

    protected $fillable = ['name', 'director_id', 'status'];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    /**
     * Director titular del proceso.
     *
     * @return BelongsTo<User, $this>
     */
    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    /**
     * Usuarios que pertenecen al proceso.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Reemplazos registrados para el director del proceso.
     *
     * @return HasMany<ProcessSubstitution, $this>
     */
    public function substitutions(): HasMany
    {
        return $this->hasMany(ProcessSubstitution::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', Status::Active);
    }

    /**
     * Procesos activos para los selects de los formularios.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return self::query()->active()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * El usuario aprueba en nombre del proceso: es su director titular o tiene un reemplazo
     * vigente hoy (el titular conserva la aprobación durante el reemplazo).
     */
    public function isApprover(User $user): bool
    {
        if ($this->director_id === $user->id) {
            return true;
        }

        return $this->substitutions()->activeOn()->where('substitute_id', $user->id)->exists();
    }

    /** Proceso activo por nombre exacto (ej. Gerencia), o null si no existe. */
    public static function named(string $name): ?self
    {
        return self::query()->active()->where('name', $name)->first();
    }

    public function toggleStatus(): void
    {
        $this->status = $this->status === Status::Active ? Status::Inactive : Status::Active;
        $this->save();
    }
}
