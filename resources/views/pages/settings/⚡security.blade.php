<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Title;
use Livewire\Component;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] #[Title('Configuración de seguridad')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    #[Locked]
    public bool $canManagePasskeys;

    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        // Comentario de contraseña actualizada, se puede usar un toast para notificar al usuario
        // Flux::toast(variant: 'success', text: __('Password updated.'));
    }

    /**
     * Load the user's passkeys.
     */
    public function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Show the delete confirmation modal.
     */
    public function confirmDelete(int $passkeyId): void
    {
        $passkey = auth()->user()->passkeys()->findOrFail($passkeyId);

        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the passkey.
     */
    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($this->deletingPasskeyId);

        $deletePasskey(auth()->user(), $passkey);

        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }

    /**
     * Handle the two-factor authentication enabled event.
     */
    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
}; ?>

<section class="w-100">

    {{-- Encabezado --}}
    <div class="mb-5">
        <h1 class="h4 mb-2">
            {{ __('Security settings') }}
        </h1>
    </div>

    {{-- Layout de Settings --}}
    <div class="row">

        {{-- Menú lateral --}}
        <div class="col-12 col-md-3 mb-4">
            <div class="card">
                <div class="card-body p-2">
                    <ul class="nav flex-column nav-pills">
                        <li class="nav-item">
                            <a href="{{ route('profile.edit') }}"
                               class="nav-link">
                                <i class="bi bi-person me-2" aria-hidden="true"></i>
                                {{ __('Profile') }}
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('security.edit') }}"
                               class="nav-link active">
                                <i class="bi bi-shield-lock me-2" aria-hidden="true"></i>
                                {{ __('Security') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Contenido --}}
        <div class="col-12 col-md-9">

            {{-- Cambio de contraseña --}}
            <div class="card mb-5">
                <div class="card-header">
                    <h4 class="mb-1">
                        {{ __('Update password') }}
                    </h4>

                    <p class="text-muted mb-0">
                        {{ __('Ensure your account is using a long, random password to stay secure') }}
                    </p>
                </div>

                <div class="card-body">

                    <form wire:submit="updatePassword">
                        <div class="row g-3">
                            {{-- Contraseña actual --}}
                            <div class="col-md-6">
                                <x-forms.input name="current_password" type="password" :label="__('Current password')"
                                               required autocomplete="current-password" />
                            </div>
                            <div class="col-md-6 d-none d-md-block"></div>

                            {{-- Nueva contraseña y confirmación --}}
                            <div class="col-md-6">
                                <x-forms.input name="password" type="password" :label="__('New password')" required autocomplete="new-password"
                                               passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" />
                            </div>
                            <div class="col-md-6">
                                <x-forms.input name="password_confirmation" type="password" :label="__('Confirm password')"
                                               required autocomplete="new-password" />
                            </div>
                        </div>

                        <div class="form-actions mt-4">
                            <button type="submit" class="btn btn-primary app-modal-submit" data-test="update-password-button"
                                    wire:loading.attr="disabled" wire:target="updatePassword">
                                <i class="bi bi-check2" wire:loading.remove wire:target="updatePassword" aria-hidden="true"></i>
                                <span class="spinner-border spinner-border-sm" wire:loading wire:target="updatePassword" aria-hidden="true"></span>
                                {{ __('Save') }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            {{-- Two Factor Authentication --}}
            @if ($canManageTwoFactor)

                <div class="card mb-5">
                    <div class="card-header">
                        <h4 class="mb-1">
                            {{ __('Two-factor authentication') }}
                        </h4>

                        <p class="text-muted mb-0">
                            {{ __('Manage your two-factor authentication settings') }}
                        </p>
                    </div>

                    <div class="card-body" wire:cloak>

                        @if ($twoFactorEnabled)

                            <p class="text-muted mb-4">
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </p>

                            <button
                                type="button"
                                class="btn btn-danger mb-4"
                                wire:click="disable"
                            >
                                {{ __('Disable 2FA') }}
                            </button>

                            <div>
                                <livewire:pages::settings.two-factor.recovery-codes
                                    :$requiresConfirmation
                                />
                            </div>

                        @else

                            <p class="text-muted mb-4">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </p>

                            <button
                                type="button"
                                class="btn btn-primary"
                                wire:click="$dispatch('start-two-factor-setup')"
                                data-bs-toggle="modal"
                                data-bs-target="#two-factor-setup-modal"
                            >
                                {{ __('Enable 2FA') }}
                            </button>

                            <livewire:pages::settings.two-factor-setup-modal
                                :requires-confirmation="$requiresConfirmation"
                            />

                        @endif

                    </div>
                </div>

            @endif

            {{-- Passkeys --}}
            @if ($canManagePasskeys)

                <div class="card mb-5">
                    <div class="card-header">
                        <h4 class="mb-1">
                            {{ __('Passkeys') }}
                        </h4>

                        <p class="text-muted mb-0">
                            {{ __('Manage your passkeys for passwordless sign-in') }}
                        </p>
                    </div>

                    <div class="card-body" wire:cloak>

                        <div class="border rounded overflow-hidden">

                            @forelse ($passkeys as $passkey)

                                <div class="d-flex align-items-center justify-content-between p-4
                                    {{ ! $loop->last ? 'border-bottom' : '' }}">

                                    <div class="d-flex align-items-center gap-3">

                                        <div class="avatar avatar-50 rounded bg-body-secondary d-flex align-items-center justify-content-center">
                                            <i class="bi bi-key fs-5 text-muted"></i>
                                        </div>

                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">

                                                <p class="mb-0 fw-medium">
                                                    {{ $passkey['name'] }}
                                                </p>

                                                @if ($passkey['authenticator'])
                                                    <span class="badge bg-secondary">
                                                        {{ $passkey['authenticator'] }}
                                                    </span>
                                                @endif

                                            </div>

                                            <p class="mb-0 text-muted small">
                                                {{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}

                                                @if ($passkey['last_used_at_diff'])
                                                    <span class="mx-1 opacity-50">/</span>

                                                    {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}
                                                @endif
                                            </p>
                                        </div>

                                    </div>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        wire:click="confirmDelete({{ $passkey['id'] }})"
                                        title="{{ __('Remove passkey') }}"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>

                                </div>

                            @empty

                                <div class="text-center p-5">

                                    <div class="avatar avatar-60 rounded bg-body-secondary d-inline-flex align-items-center justify-content-center mb-3">
                                        <i class="bi bi-key fs-3 text-muted"></i>
                                    </div>

                                    <h6 class="mb-1">
                                        {{ __('No passkeys yet') }}
                                    </h6>

                                    <p class="text-muted mb-0">
                                        {{ __('Add a passkey to sign in without a password') }}
                                    </p>

                                </div>

                            @endforelse

                        </div>

                        <div class="mt-4">
                            <x-passkey-registration />
                        </div>

                    </div>
                </div>

            @endif

        </div>

    </div>

    {{-- Modal eliminar passkey --}}
    <div
        class="modal fade"
        id="delete-passkey-modal"
        tabindex="-1"
        wire:ignore.self
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ __('Remove passkey') }}
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="{{ __('Close') }}"
                        wire:click="closeDeleteModal"
                    ></button>
                </div>

                <div class="modal-body">

                    <p class="mb-0">
                        {{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}
                    </p>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                        wire:click="closeDeleteModal"
                    >
                        {{ __('Cancel') }}
                    </button>

                    <button
                        type="button"
                        class="btn btn-danger"
                        wire:click="deletePasskey"
                    >
                        {{ __('Remove passkey') }}
                    </button>

                </div>

            </div>
        </div>
    </div>

</section>
