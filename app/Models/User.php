<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\HumanResources\App\Models\HrCharacteristicOption;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $lastname
 * @property string $identification
 * @property string $email
 * @property UserStatus $status
 * @property int|null $role_id
 * @property int|null $service_point_id
 * @property int|null $process_id
 * @property int|null $position_id
 * @property bool $can_request_supplies
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['identification', 'name', 'lastname', 'process_id', 'position_id', 'can_request_supplies', 'email', 'status', 'role_id', 'service_point_id', 'pending_service_point_id', 'password', 'profile_photo_path'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    use HasRoles;
    use SoftDeletes;
    use Userstamps;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'must_change_password' => 'boolean',
            'can_request_supplies' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->fullName(), true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Summary of fullName
     */
    public function fullName(): string
    {
        return $this->name.' '.$this->lastname;
    }

    /**
     * Búsqueda por nombre, apellido, correo o identificación.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('identification', 'like', "%{$term}%")
                ->orWhere('lastname', 'like', "%{$term}%");
        }));
    }

    /**
     * Rol primario del usuario (users.role_id), el que se muestra en la interfaz.
     *
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Proceso (área) al que pertenece el usuario.
     *
     * @return BelongsTo<Process, $this>
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * Cargo del usuario (Talento Humano → Cargos). Incluye cargos eliminados para mostrarlos.
     *
     * @return BelongsTo<HrCharacteristicOption, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(HrCharacteristicOption::class, 'position_id')->withTrashed();
    }

    /** Nombre del cargo para las firmas (consulta el cargo si no está cargado). */
    public function positionName(): ?string
    {
        return $this->position_id === null ? null : $this->loadMissing('position')->position?->name;
    }

    /**
     * Sede a la que pertenece el usuario.
     *
     * @return BelongsTo<ServicePoint, $this>
     */
    public function servicePoint(): BelongsTo
    {
        return $this->belongsTo(ServicePoint::class);
    }

    /**
     * Sede preferida para visualizar pendientes de farmacia; null muestra todas.
     *
     * @return BelongsTo<ServicePoint, $this>
     */
    public function pendingServicePoint(): BelongsTo
    {
        return $this->belongsTo(ServicePoint::class, 'pending_service_point_id');
    }

    /**
     * Indica si el usuario pertenece a la Oficina Principal (sede código 1),
     * que gestiona información de todas las sedes.
     * loadMissing: la sede se consulta una sola vez por petición, aun en acciones masivas.
     */
    public function worksAtHeadquarters(): bool
    {
        return (bool) $this->loadMissing('servicePoint')->servicePoint?->isHeadquarters();
    }
}
