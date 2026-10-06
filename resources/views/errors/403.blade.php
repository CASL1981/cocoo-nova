<!doctype html>
<html lang="es"
      class="theme-fs-sm"
      data-bs-theme="light"
      data-bs-theme-color="default"
      dir="ltr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }} - Acceso denegado</title>

    <link rel="shortcut icon"
          href="{{ asset('assets/images/favicon.ico') }}">

    <link rel="stylesheet"
          href="{{ asset('assets/css/core/libs.min.css') }}">

    <link rel="stylesheet"
          href="{{ asset('assets/css/qompac-ui.min.css') }}?v=2.0.0">

    <link rel="stylesheet"
          href="{{ asset('assets/css/custom.min.css') }}?v=2.0.0">

    <link rel="stylesheet"
          href="{{ asset('assets/css/rtl.min.css') }}?v=2.0.0">

    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bs-body-bg);
        }

        .error-card {
            max-width: 560px;
            width: 100%;
        }

        .error-code {
            font-size: 6rem;
            line-height: 1;
            font-weight: 700;
            color: var(--bs-primary);
        }

        .error-icon {
            width: 80px;
            height: 80px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--bs-primary-bg-subtle);
            color: var(--bs-primary);
            margin-bottom: 1.5rem;
        }

        .error-icon svg {
            width: 42px;
            height: 42px;
        }
    </style>

    <script>
        (() => {
            const storage = localStorage.getItem('quisetting-html');

            if (!storage) {
                return;
            }

            try {
                const config = JSON.parse(storage);
                const settings = config.setting ?? {};
                const html = document.documentElement;

                const theme = settings.theme_scheme?.value;
                const direction = settings.theme_scheme_direction?.value;
                const fontSize = settings.theme_font_size?.value;
                const themeColor = settings.theme_color?.value;

                if (theme) {
                    html.setAttribute('data-bs-theme', theme);
                }

                if (direction) {
                    html.setAttribute('dir', direction);
                }

                if (fontSize) {
                    html.classList.remove(
                        'theme-fs-sm',
                        'theme-fs-md',
                        'theme-fs-lg'
                    );

                    html.classList.add(fontSize);
                }

                if (themeColor) {
                    html.setAttribute(
                        'data-bs-theme-color',
                        themeColor.replace('theme-color-', 'color-')
                    );
                }

                const colors = settings.theme_color?.colors ?? {};

                Object.entries(colors).forEach(([key, value]) => {
                    const prefixPosition = key.indexOf('}');

                    if (prefixPosition === -1) {
                        return;
                    }

                    const normalizedKey =
                        `--bs-${key.slice(prefixPosition + 2)}`;

                    html.style.setProperty(
                        normalizedKey,
                        value
                    );
                });

            } catch (error) {
                console.warn('Qompac 403 theme:', error);
            }
        })();
    </script>
</head>

<body>

<div class="error-page px-3">

    <div class="card error-card border-0 shadow-sm">

        <div class="card-body text-center p-5">

            <div class="error-icon">
                <svg viewBox="0 0 24 24"
                     fill="currentColor"
                     aria-hidden="true">
                    <path d="M12 2 4 5v6c0 5.25 3.4 10.17 8 11 4.6-.83 8-5.75 8-11V5l-8-3Zm0 3.18 5 1.87V11c0 3.8-2.23 7.79-5 8.75C9.23 18.79 7 14.8 7 11V7.05l5-1.87Zm-1 3.82v4h2V9h-2Zm0 5v2h2v-2h-2Z"/>
                </svg>
            </div>

            <div class="error-code mb-3">
                403
            </div>

            <h2 class="mb-2">
                Acceso no permitido
            </h2>

            <p class="text-muted mb-4">
                No tienes permisos suficientes para acceder
                a este recurso.
            </p>

            <p class="small text-muted mb-4">
                {{ $exception->getMessage() ?: 'Si consideras que deberías tener acceso, comunícate con el administrador del sistema.' }}
            </p>

            <a href="{{ route('dashboard') }}"
               class="btn btn-primary">

                <svg class="me-1"
                     width="18"
                     height="18"
                     viewBox="0 0 24 24"
                     fill="currentColor">
                    <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8h5Z"/>
                </svg>

                Ir al inicio
            </a>

        </div>

    </div>

</div>

</body>
</html>
