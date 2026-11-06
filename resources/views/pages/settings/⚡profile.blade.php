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
use App\Models\ServicePoint;
use Illuminate\Validation\Rule;

new #[Layout('layouts.app')] #[Title('Configuración del perfil')] class extends Component {

    use ProfileValidationRules;
    use WithFileUploads;

    public ?TemporaryUploadedFile $photo = null;

    public string $name = '';
    public string $email = '';

    // Sede preferida para visualizar pendientes de farmacia; vacío = todas las sedes.
    public string $pendingServicePointId = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->pendingServicePointId = (string) Auth::user()->pending_service_point_id;
    }

    /**
     * Guarda la preferencia de visualización de pendientes de farmacia.
     */
    public function updatePendingPreference(): void
    {
        $validated = $this->validate([
            'pendingServicePointId' => ['nullable', 'integer', Rule::exists(ServicePoint::class, 'id')->whereNull('deleted_at')],
        ], attributes: ['pendingServicePointId' => 'sede de pendientes']);

        Auth::user()->update([
            'pending_service_point_id' => $validated['pendingServicePointId'] ?: null,
        ]);

        $this->dispatch('pending-preference-updated');
    }

    /** Sedes disponibles como "código - nombre". */
    #[Computed]
    public function servicePointOptions(): array
    {
        return ServicePoint::query()->orderBy('code')->get(['id', 'code', 'name'])
            ->mapWithKeys(fn (ServicePoint $point) => [$point->id => "{$point->code} - {$point->name}"])
            ->all();
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
        <x-forms.section title="Foto de perfil" icon="bi-camera" class="mb-4">
            <div class="col-12 d-flex flex-wrap align-items-center gap-4">

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

                <div class="flex-grow-1" style="max-width: 28rem;">
                    <x-forms.field id="photo" name="photo" hint="Formatos JPG, PNG o WEBP.">
                        <input id="photo" type="file"
                            wire:model="photo"
                            @class(['form-control', 'is-invalid' => $errors->has('photo')])
                            accept=".jpg,.jpeg,.png,.webp" aria-label="Foto de perfil">
                    </x-forms.field>

                    <button type="button"
                            wire:click="updateProfilePhoto"
                            wire:loading.attr="disabled" wire:target="updateProfilePhoto,photo"
                            class="btn btn-primary app-modal-submit mt-3">
                        <i class="bi bi-upload" wire:loading.remove wire:target="updateProfilePhoto" aria-hidden="true"></i>
                        <span class="spinner-border spinner-border-sm" wire:loading wire:target="updateProfilePhoto" aria-hidden="true"></span>
                        Actualizar foto
                    </button>
                </div>

            </div>
        </x-forms.section>
        {{-- End Formulario de actualización de la foto del perfil --}}

        <form wire:submit="updateProfileInformation" class="w-100 my-4">
          <x-forms.section title="Datos de la cuenta" icon="bi-person">

            {{-- Nombre --}}
            <div class="col-md-6">
                <x-forms.input name="name" :label="__('Name')" required autofocus autocomplete="name" />
            </div>

            {{-- Correo electrónico --}}
            <div class="col-md-6">
                <x-forms.input name="email" type="email" :label="__('Email')" required autocomplete="email" />


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


          </x-forms.section>

            {{-- Botón guardar --}}
            <div class="form-actions mt-3">
                <button type="submit" class="btn btn-primary app-modal-submit" data-test="update-profile-button"
                        wire:loading.attr="disabled" wire:target="updateProfileInformation">
                    <i class="bi bi-check2" wire:loading.remove wire:target="updateProfileInformation" aria-hidden="true"></i>
                    <span class="spinner-border spinner-border-sm" wire:loading wire:target="updateProfileInformation" aria-hidden="true"></span>
                    {{ __('Save') }}
                </button>
            </div>

        </form>

        {{-- Preferencia de visualización de pendientes de farmacia --}}
        <form wire:submit="updatePendingPreference" class="w-100 my-4">
            <x-forms.section title="Preferencias de farmacia" icon="bi-sliders">
                <div class="col-md-6">
                    <x-forms.select name="pendingServicePointId" id="pending-service-point" label="Sede para visualizar pendientes"
                                    :options="$this->servicePointOptions" placeholder="Todas las sedes"
                                    hint="Si eliges una sede, el listado de pendientes solo mostrará los de ese destino." />
                </div>
            </x-forms.section>

            <div class="form-actions mt-3">
                <button type="submit" class="btn btn-primary app-modal-submit" wire:loading.attr="disabled" wire:target="updatePendingPreference">
                    <i class="bi bi-check2" wire:loading.remove wire:target="updatePendingPreference" aria-hidden="true"></i>
                    <span class="spinner-border spinner-border-sm" wire:loading wire:target="updatePendingPreference" aria-hidden="true"></span>
                    Guardar preferencia
                </button>
                <span x-data="{ saved: false }" x-on:pending-preference-updated.window="saved = true; setTimeout(() => saved = false, 2000)"
                      x-show="saved" x-cloak class="text-success small">Preferencia guardada</span>
            </div>
        </form>

    </x-pages::settings.layout>

</section>
