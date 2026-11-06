<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\UserStatus;
use App\Models\User;
use Livewire\Attributes\Computed;

/**
 * Buscador de usuarios activos para <x-forms.search-select> (directores, reemplazos).
 *
 * El componente define selectUser() para guardar el id elegido en su formulario;
 * la validación del id se hace siempre en el Form del servidor.
 */
trait SearchesActiveUsers
{
    public string $userSearch = '';

    abstract public function selectUser(string $id): void;

    /**
     * Hasta 15 usuarios activos que coinciden con el texto (nombre, apellido,
     * identificación o correo). Sin texto lista los primeros por nombre, para que
     * el campo se despliegue al enfocarlo como un Select2.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function userResults(): array
    {
        $term = trim($this->userSearch);

        return User::query()->search($term)
            ->where('status', UserStatus::Active)
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'lastname', 'identification'])
            ->mapWithKeys(fn (User $user) => [$user->id => self::userLabel($user)])
            ->all();
    }

    /** Texto visible de un usuario en el buscador. */
    protected static function userLabel(User $user): string
    {
        return $user->fullName().' · '.$user->identification;
    }
}
