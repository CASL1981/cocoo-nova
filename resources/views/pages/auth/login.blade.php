<x-layouts::auth :title="__('Log in')">
    <section class="login-content overflow-hidden">
        <div class="row no-gutters align-items-center sing-in-from vh-100">
            <div class="col-md-12 col-lg-6 align-self-center">
                <a href="{{ url('/') }}"
                    class="navbar-brand d-flex align-items-center mb-3 justify-content-center text-primary">
                    <div class="logo-normal">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="img-fluid" style="width: 80px; height: auto;">
                    </div>
                    <h2 class="logo-title ms-3 mb-0" >{{ config('app.name', 'COCOO') }}</h2>
                </a>
                <div class="row justify-content-center pt-5">
                    <div class="col-md-9">
                        <div class="card  d-flex justify-content-center mb-0 auth-card iq-auth-form">
                            <div class="card-body">
                                <h2 class="mb-2 text-center">Iniciar Sesión</h2>
                                <p class="text-center">Inicia sesión para mantenerte conectado.</p>

                                <!-- Mostrar errores de validación -->
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif


                                <!-- Formulario de Laravel -->
                                <form method="POST" action="{{ route('login') }}">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" id="email"
                                                    aria-describedby="email" placeholder="xyz@example.com" required autofocus>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="password" class="form-label">{{ __('Password') }}</label>
                                                <input type="password" class="form-control" name="password" id="password"
                                                    aria-describedby="password" placeholder="xxxx" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-12 d-flex justify-content-between">
                                            <div class="form-check mb-3">
                                                <input type="checkbox" class="form-check-input" name="remember" id="remember">
                                                <label class="form-check-label" for="remember">Recordarme</label>
                                            </div>
                                            <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                                    </div>
                                    <p class="text-center my-3">or sign in with other accounts?</p>
                                    <div class="d-flex justify-content-center">
                                        <ul class="list-group list-group-horizontal list-group-flush">
                                            <li class="list-group-item border-0 pb-0">.</li>
                                            <li class="list-group-item border-0 pb-0">.</li>
                                            <li class="list-group-item border-0 pb-0">.</li>
                                            <li class="list-group-item border-0 pb-0">.</li>
                                        </ul>
                                    </div>
                                    <p class="mt-3 text-center">........</p>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-lg-block d-none bg-primary p-0 overflow-hidden">
                <img src="{{ asset('images/auth/1.svg') }}" class="img-fluid gradient-main w-100 h-100" alt="images" style="object-fit: cover;" loading="lazy">
            </div>
        </div>
    </section>
</x-layouts::auth>
