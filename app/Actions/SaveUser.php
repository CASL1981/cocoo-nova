<?php

namespace App\Actions;

use App\Models\Role;
use App\Models\User;
use App\Notifications\Users\TemporaryPasswordAssigned;
use App\Notifications\Users\UserCreatedConfirmation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Crea o actualiza un usuario.
 *
 * - Al CREAR: genera una contraseña temporal, la envía por correo al nuevo
 *   usuario, notifica al administrador (sin la clave) y marca
 *   must_change_password para forzar su cambio en el primer inicio de sesión.
 * - Al EDITAR: nunca toca la contraseña; ese flujo quedó fuera de aquí por diseño
 *   (todo cambio de clave pasa por la contraseña temporal o por "olvidé mi clave").
 */
final class SaveUser
{
    public function execute(array $data, ?User $user = null, ?UploadedFile $photo = null): User
    {
        $isCreating = $user === null;
        $user ??= new User();
        $previousPath = $user->profile_photo_path;

        if ($photo) {
            $data['profile_photo_path'] = $photo->store('profile-photos', 'public');
        }

        $temporaryPassword = null;

        if ($isCreating) {
            $temporaryPassword = $this->generateTemporaryPassword();
            $data['password'] = Hash::make($temporaryPassword);
        }

        $user->fill($data);

        if ($isCreating) {
            // Se fuerza aparte del fill() porque must_change_password no es
            // mass-assignable a propósito (ver la migración): ningún formulario
            // debe poder alterarla, solo este punto exacto del código.
            $user->must_change_password = true;
        }

        $user->save();

        if ($isCreating) {
            $user->notify(new TemporaryPasswordAssigned($temporaryPassword));

            // Solo notifica al admin si la creación ocurrió dentro de una sesión
            // autenticada (por ejemplo, no aplicaría en un seeder de consola).
            if ($creator = Auth::user()) {
                $creator->notify(new UserCreatedConfirmation($user));
            }
        }

        if ($photo && $previousPath) {
            Storage::disk('public')->delete($previousPath);
        }

        return $user;
    }

    /**
     * Genera una contraseña temporal sin símbolos, para que sea fácil de
     * transcribir a mano si el correo se abre en un dispositivo distinto
     * al que se usará para iniciar sesión.
     */
    private function generateTemporaryPassword(): string
    {
        return Str::password(length: 12, letters: true, numbers: true, symbols: false, spaces: false);
    }
}
