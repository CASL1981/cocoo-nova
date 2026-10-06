<x-layouts::auth :title="__('Reset password')">
<div class="wrapper">
        <section class="login-content overflow-hidden">
            <div class="row m-0 align-items-center  vh-100">
                <div class="col-md-12 col-lg-6 align-self-center">
                    <a href="{{ url('/') }}"
                        class="navbar-brand d-flex align-items-center mb-3 justify-content-center text-primary">
                        <div class="logo-normal">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="img-fluid" style="width: 80px; height: auto;">
                        </div>
                        <h2 class="logo-title ms-3 mb-0">{{ config('app.name', 'COCOO') }}</h2>
                    </a>
                    <div class="row justify-content-center pt-5">
                        <div class="col-lg-8">
                            <div class="card d-flex justify-content-center mb-0">
                                <div class="card-body p-4 p-lg-5">
                                    {{-- Encabezado --}}
                                    <div class="text-center mb-4">
                                        <h4 class="mb-2">{{ __('Reset password') }}</h4>
                                        <p class="text-muted mb-0">{{ __('Por favor, introduzca su nueva contraseña a continuación.') }}</p>
                                    </div>

                                    {{-- Estado de sesión --}}
                                    <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

                                    <form method="POST" action="{{ route('password.update') }}">
                                        @csrf

                                        {{-- Token --}}
                                        <input type="hidden" name="token" value="{{ request()->route('token') }}">
                                        {{-- Email --}}
                                        <div class="mb-3">
                                            <label for="email" class="form-label">{{ __('Email') }}</label>

                                            <input id="email" name="email" type="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                value="{{ request('email') }}" required autocomplete="email">

                                            @error('email')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                        </div>

                                        {{-- Nueva contraseña --}}
                                        <div class="mb-3">
                                            <label for="password" class="form-label">{{ __('Password') }}</label>

                                            <input id="password" name="password" type="password" aria-describedby="password"
                                                class="form-control @error('password') is-invalid @enderror" required
                                                autocomplete="new-password" placeholder="{{ __('Password') }}"
                                                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}">

                                            @error('password')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                        </div>

                                        {{-- Confirmar contraseña --}}
                                        <div class="mb-4">
                                            <label for="password_confirmation" class="form-label">{{ __('Confirm password') }}</label>

                                            <input id="password_confirmation" name="password_confirmation" type="password"
                                                class="form-control" required autocomplete="new-password"
                                                placeholder="{{ __('Confirm password') }}"
                                                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}">

                                            @error('password_confirmation')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                        </div>

                                        {{-- Botón --}}
                                        <button type="submit" class="btn btn-primary w-100" data-test="reset-password-button">
                                            {{ __('Reset password') }}
                                        </button>
                                    </form>
                                </div>
                                {{-- Regresar al login --}}
                                <div class="text-center mt-4">
                                    <span class="text-muted small">{{ __('Or, return to') }}</span>
                                    <a href="{{ route('login') }}" class="ms-1">{{ __('Sign in') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 d-lg-block d-none bg-primary p-0  overflow-hidden">
                    <img src="{{ asset('images/auth/1.svg') }}" class="gradient-main w-100 h-100" alt="images"
                        loading="lazy">
                </div>
            </div>
        </section>
    </div>
</x-layouts::auth>
