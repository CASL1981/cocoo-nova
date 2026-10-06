<!doctype html>
<html lang="es" class="theme-fs-sm" data-bs-theme="light" data-bs-theme-color="default" dir="ltr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    {{-- <title data-setting="COCOO" data-rightJoin=" Administrador de procesos contables">@yield('Title', 'COCOO - Dashboard')</title> --}}
    <title>COCOO - {{ $title ?? 'Dashboard' }}</title>
    <!-- Favicons -->
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="  ">

    @include('layouts.partials.sidebar')

    <main class="main-content">
        <div class="position-relative">
            <!--Nav Start-->
            @include('layouts.partials.navbar')

            <!--Nav End-->
        </div>
        <div class="content-inner container-fluid pb-0" id="page_layout">
            {{ $slot }}
        </div>
        <!-- Footer Section Start -->

        @include('layouts.partials.footer')

        <!-- Footer Section End -->
    </main>
    <!-- Wrapper End-->

    @include('layouts.partials.customizer')

    <!-- Vite: Qompac propio + Livewire -->
    {{-- @vite(['resources/js/app.js']) --}}

    @livewireScriptConfig

    //components toasts para los mensajes de alerta
    <x-toast-container />
    <livewire:audit.userstamps-modal />
</body>

</html>
