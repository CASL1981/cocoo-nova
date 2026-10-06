<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

new #[Layout('layouts.app')] #[Title('Profile settings')] class extends Component {

    use ProfileValidationRules;
    use WithFileUploads;

    public ?TemporaryUploadedFile $photo = null;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Comentario de usuario actualizado, se puede usar un toast para notificar al usuario
        //
    }

    /**
     * Update the profile photo for the currently authenticated user.
     */
    public function updateProfilePhoto(): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp','max:2048',],
        ]);

        $user = Auth::user();

        $oldPhoto = $user->profile_photo_path;

        $newPhoto = $this->photo->store('profile-photos', 'public');

        $user->update([
            'profile_photo_path' => $newPhoto,
        ]);

        if ($oldPhoto) {
            Storage::disk('public')->delete($oldPhoto);
        }

        $this->reset('photo');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && !Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return !Auth::user() instanceof MustVerifyEmail || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-100">

    <h4 class="visually-hidden">
        {{ __('Profile settings') }}
    </h4>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Actualiza tu foto, nombre y dirección de correo electrónico')">

        {{-- Formulario de actualización de la foto del perfil --}}
        <div class="mb-4">
            <label class="form-label">Foto de perfil</label>

            <div class="d-flex align-items-center gap-4">

                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}"
                        class="rounded-circle"
                        style="width:100px;height:100px;object-fit:cover;">
                @elseif (Auth::user()->profile_photo_path)
                    <img src="{{ Storage::disk('public')->url(Auth::user()->profile_photo_path) }}"
                        class="rounded-circle"
                        style="width:100px;height:100px;object-fit:cover;">
                @else
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                        style="width:100px;height:100px;font-size:2rem;">
                        {{ Auth::user()->initials() }}
                    </div>
                @endif

                <div>
                    <input type="file"
                        wire:model="photo"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp">

                    @error('photo')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror

                    <button type="button"
                            wire:click="updateProfilePhoto"
                            wire:loading.attr="disabled"
                            class="btn btn-primary mt-2">

                        <span wire:loading.remove wire:target="updateProfilePhoto">
                            Actualizar foto
                        </span>

                        <span wire:loading wire:target="updateProfilePhoto">
                            Guardando...
                        </span>
                    </button>
                </div>

            </div>
        </div>
        {{-- End Formulario de actualización de la foto del perfil --}}

        <form wire:submit="updateProfileInformation" class="w-100 d-flex flex-column gap-4 my-4">

            {{-- Nombre --}}
            <div class="form-group">
                <label for="name" class="form-label">
                    {{ __('Name') }}
                </label>

                <input id="name" type="text" wire:model="name" class="form-control" required autofocus
                    autocomplete="name" />

                @error('name')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>


            {{-- Correo electrónico --}}
            <div class="form-group">

                <label for="email" class="form-label">
                    {{ __('Email') }}
                </label>

                <input id="email" type="email" wire:model="email" class="form-control" required
                    autocomplete="email" />

                @error('email')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror


                {{-- Correo no verificado --}}
                @if ($this->hasUnverifiedEmail)

                    <div class="mt-3">

                        <p class="text-muted mb-2">
                            {{ __('Your email address is unverified.') }}
                        </p>

                        <a href="#" class="text-primary text-decoration-none small"
                            wire:click.prevent="resendVerificationNotification">
                            {{ __('Click here to re-send the verification email.') }}
                        </a>

                        {{-- Mensaje de confirmación --}}
                        @if (session('status') === 'verification-link-sent')
                            <div class="alert alert-success mt-3 mb-0" role="alert">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </div>
                        @endif

                    </div>

                @endif

            </div>


            {{-- Botón guardar --}}
            <div class="d-flex align-items-center gap-3">

                <button type="submit" class="btn btn-primary" data-test="update-profile-button">
                    {{ __('Save') }}
                </button>

            </div>

        </form>

    </x-pages::settings.layout>

</section>
