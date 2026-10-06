<div class="row">
    {{-- Menú de configuración --}}
    <div class="col-12 col-md-3 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Settings') }}</h5>
            </div>

            <div class="card-body p-2">
                <ul class="nav flex-column nav-pills">

                    <li class="nav-item">
                        <a href="{{ route('profile.edit') }}"
                            class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                            <i class="icon-20 me-2">
                                {{-- Icono --}}
                            </i>
                            {{ __('Profile') }}
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('security.edit') }}"
                            class="nav-link {{ request()->routeIs('security.edit') ? 'active' : '' }}">
                            <i class="icon-20 me-2">
                                {{-- Icono --}}
                            </i>
                            {{ __('Security') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Contenido --}}
    <div class="col-12 col-md-9">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-1">
                    {{ $heading ?? '' }}
                </h4>

                @if (!empty($subheading))
                    <p class="mb-0 text-muted">
                        {{ $subheading }}
                    </p>
                @endif
            </div>

            <div class="card-body">
                {{ $slot }}
            </div>
        </div>
    </div>

</div>
