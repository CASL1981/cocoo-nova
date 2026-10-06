<x-layouts::auth :title="__('Confirm password')">

    <div class="container-fluid py-5">
        <div class="row justify-content-center">

            <div class="col-12 col-md-8 col-lg-5 col-xl-4">

                <div class="card shadow-sm">

                    <div class="card-body p-4 p-lg-5">

                        {{-- Encabezado --}}
                        <div class="text-center mb-4">

                            <h4 class="mb-2">
                                {{ __('Confirm password') }}
                            </h4>

                            <p class="text-muted mb-0">Esta es una zona segura de la aplicación. Por favor, confirme su contraseña antes de continuar.</p>

                        </div>

                        {{-- Estado de sesión --}}
                        <x-auth-session-status
                            class="mb-4 text-center"
                            :status="session('status')"
                        />

                        {{-- Confirmación mediante Passkey --}}
                        <div class="mb-4">

                            <x-passkey-verify
                                options-route="passkey.confirm-options"
                                submit-route="passkey.confirm"
                                :label="__('Confirm with passkey')"
                                :loading-label="__('Confirming...')"
                                :separator="__('Or confirm with password')"
                            />

                        </div>

                        {{-- Confirmación mediante contraseña --}}
                        <form
                            method="POST"
                            action="{{ route('password.confirm.store') }}"
                        >
                            @csrf

                            <div class="mb-4">

                                <label
                                    for="password"
                                    class="form-label"
                                >
                                    {{ __('Password') }}
                                </label>

                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    required
                                    autocomplete="current-password"
                                    placeholder="{{ __('Password') }}"
                                >

                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                                data-test="confirm-password-button"
                            >
                                {{ __('Confirm') }}
                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>
    </div>

</x-layouts::auth>
