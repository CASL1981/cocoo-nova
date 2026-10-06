<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>
    {{ filled($title ?? null) ? $title . ' - ' . config('app.name', 'COCOO') : config('app.name', 'COCOO') }}
</title>

<!-- Favicons -->
<link rel="shortcut icon" href="{{ asset('images/logo.png') }}" />

{{-- Estilos de Qompac UI --}}

<!-- Library / Plugin Css Build -->
<link rel="stylesheet" href="{{ asset('assets/css/core/libs.min.css') }}" />

<!-- qompac-ui Design System Css -->
<link rel="stylesheet" href="{{ asset('assets/css/qompac-ui.min.css') }}?v=2.0.0" />

<!-- Custom Css -->
<link rel="stylesheet" href="{{ asset('assets/css/custom.min.css') }}?v=2.0.0" />

<!-- RTL Css -->
<link rel="stylesheet" href="{{ asset('assets/css/rtl.min.css') }}?v=2.0.0" />

<!-- Customizer Css -->
<link rel="stylesheet" href="{{ asset('assets/css/customizer.min.css') }}?v=2.0.0" />

<!-- Google Font -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@100;200;300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">
