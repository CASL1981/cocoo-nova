<!doctype html>
<html lang="es"
      class="theme-fs-sm"
      data-bs-theme="light"
      data-bs-theme-color="default"
      dir="ltr">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>
        {{ config('app.name') }} - Error interno
    </title>

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

        .error-page-500 {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bs-body-bg);
            padding: 2rem;
        }

        .error-card-500 {
            width: 100%;
            max-width: 560px;
        }

        .error-icon-500 {
            width: 90px;
            height: 90px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--bs-danger-bg-subtle);
            color: var(--bs-danger);
        }

        .error-icon-500 i {
            font-size: 2.75rem;
        }

        .error-code-500 {
            font-size: 5rem;
            line-height: 1;
            font-weight: 700;
            color: var(--bs-danger);
            margin-bottom: 1rem;
        }

        .error-title-500 {
            color: var(--bs-heading-color);
        }

        .error-text-500 {
            color: var(--bs-secondary-color);
            max-width: 440px;
            margin-left: auto;
            margin-right: auto;
        }

    </style>

    {{-- Recuperar configuración del Customizer --}}
    <script>
        (() => {

            const storage =
                localStorage.getItem('quisetting-html');

            if (!storage) {
                return;
            }

            try {

                const config = JSON.parse(storage);

                const settings =
                    config.setting ?? {};

                const html =
                    document.documentElement;

                const theme =
                    settings.theme_scheme?.value;

                const direction =
                    settings.theme_scheme_direction?.value;

                const fontSize =
                    settings.theme_font_size?.value;

                const themeColor =
                    settings.theme_color?.value;

                if (theme) {
                    html.setAttribute(
                        'data-bs-theme',
                        theme
                    );
                }

                if (direction) {
                    html.setAttribute(
                        'dir',
                        direction
                    );
                }

                if (fontSize) {

                    html.classList.remove(
                        'theme-fs-sm',
                        'theme-fs-md',
                        'theme-fs-lg'
                    );

                    html.classList.add(
                        fontSize
                    );
                }

                if (themeColor) {

                    let color = themeColor;

                    if (
                        color.startsWith(
                            'theme-color-'
                        )
                    ) {
                        color =
                            color.replace(
                                'theme-color-',
                                'color-'
                            );
                    }

                    html.setAttribute(
                        'data-bs-theme-color',
                        color
                    );
                }

                const colors =
                    settings.theme_color?.colors ?? {};

                Object.entries(colors)
                    .forEach(([key, value]) => {

                        const marker =
                            String.fromCharCode(123, 123) +
                            'prefix' +
                            String.fromCharCode(125, 125);

                        const normalizedKey =
                            key.replace(
                                marker,
                                'bs-'
                            );

                        html.style.setProperty(
                            normalizedKey,
                            value
                        );

                    });

            } catch (error) {

                console.warn(
                    'Qompac 500 theme:',
                    error
                );

            }

        })();
    </script>

</head>

<body>

    <div class="error-page-500">

        <div class="card error-card-500 border-0 shadow-sm">

            <div class="card-body text-center p-5">

                <div class="error-icon-500">
                    <svg
                        width="42"
                        height="42"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path d="M12 2.5c.7 0 1.3.35 1.65.95l8.15 14.1c.7 1.2-.17 2.7-1.55 2.7H3.75c-1.38 0-2.25-1.5-1.55-2.7l8.15-14.1c.35-.6.95-.95 1.65-.95Zm0 4.25a1 1 0 0 0-1 1v4.75a1 1 0 1 0 2 0V7.75a1 1 0 0 0-1-1Zm0 9.25a1.1 1.1 0 1 0 0 2.2 1.1 1.1 0 0 0 0-2.2Z"/>
                    </svg>
                </div>

                <div class="error-code-500">
                    500
                </div>

                <h2 class="error-title-500 mb-3">
                    Error interno del servidor
                </h2>

                <p class="error-text-500 mb-4">
                    Ha ocurrido un problema inesperado al
                    procesar tu solicitud.
                </p>

                <p class="error-text-500 small mb-4">
                    Por favor, intenta nuevamente en unos
                    momentos. Si el problema continúa,
                    comunícate con el administrador del sistema.
                </p>

                <a href="{{ url('/') }}"
                   class="btn btn-primary">

                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        class="me-1"
                        aria-hidden="true"
                    >
                        <path d="M12 3 2 12h3v8h6v-5h2v5h6v-8h3L12 3Zm0 2.7 5 5V18h-2v-5H9v5H7v-7.3l5-5Z"/>
                    </svg>

                    Ir al inicio

                </a>

                <button type="button"
                        class="btn btn-outline-secondary ms-2"
                        onclick="window.location.reload();">

                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        class="me-1"
                        aria-hidden="true"
                    >
                        <path d="M12 5a7 7 0 1 1-6.32 4H3l4-4 4 4H8.1A5 5 0 1 0 12 7a5 5 0 0 0 4.33 2.5l1.3-1.3A7 7 0 0 1 12 5Z"/>
                    </svg>

                    Reintentar

                </button>

            </div>

        </div>

    </div>

</body>
</html>
