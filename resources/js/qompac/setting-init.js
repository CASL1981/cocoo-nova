const settingOptions = {
    saveLocal: 'localStorage',

    storeKey: 'quisetting-html',

    setting: {
        app_name: {
            value: 'Qompac UI',
        },

        theme_scheme_direction: {
            value: 'ltr',
        },

        theme_scheme: {
            value: 'light',
        },

        theme_color: {
            colors: {
                '--{{prefix}}primary': '#7016d0',
                '--{{prefix}}secondary': '#aca4bc',
                '--{{prefix}}tertiray': '#3FF0B9',
                '--{{prefix}}success': '#1AA053',
                '--{{prefix}}danger': '#C03221',
                '--{{prefix}}warning': '#F16A1B',
            },

            value: 'theme-color-default',
        },

        theme_font_size: {
            value: 'theme-fs-sm',
        },

        page_layout: {
            value: 'container',
        },

        sidebar_color: {
            value: 'sidebar-white',
        },

        sidebar_type: {
            value: [],
        },

        sidebar_menu_style: {
            value: 'sidebar-default navs-rounded-all',
        },
    },
};

function selectTheme(theme) {
    switch (theme) {
        case '0':
            break;

        default:
            break;
    }

    return settingOptions;
}

export const initSetting = () => {

    // --------------------------------------------------
    // Theme recibido por query string
    // --------------------------------------------------
    const theme = IQUtils.getQueryString('theme');

    let options = settingOptions;

    if (theme !== '' && theme !== null) {
        options = selectTheme(theme);
    }

    // --------------------------------------------------
    // Crear instancia global de IQSetting
    // --------------------------------------------------
    const setting = new IQSetting(options);

    /*
     * Compatibilidad con los scripts legacy de Qompac.
     *
     * setting.js expone inicialmente la clase IQSetting.
     * Qompac espera posteriormente que window.IQSetting
     * sea la instancia creada.
     */
    window.IQSetting = setting;

    // --------------------------------------------------
    // Layout recibido por query string
    // --------------------------------------------------
    const layout = IQUtils.getQueryString('layout');

    if (layout !== '' && layout !== null) {

        switch (layout) {

            case 'dark':
                setting.theme_scheme('dark');
                break;

            case 'semi-dark':
                setting.theme_scheme('light');
                setting.sidebar_color('sidebar-dark');
                break;

            case 'full-width':
                setting.page_layout('container-fluid');
                break;

            case 'mini':
                setting.sidebar_type(['sidebar-mini']);
                setting.sidebar_menu_style(
                    'sidebar-default navs-rounded-all'
                );
                setting.page_layout('container-fluid');
                break;

            case 'sidebar-pill-one':
                setting.setSettingColorChoice(
                    'theme_color',
                    {
                        key: '--{{prefix}}primary',
                        value: '#fa6aae',
                    }
                );

                setting.theme_color('theme-color-pink');

                setting.sidebar_menu_style(
                    'sidebar-default navs-pill'
                );

                setting.page_layout('container-fluid');
                break;

            case 'sidebar-pill-all':
                setting.setSettingColorChoice(
                    'theme_color',
                    {
                        key: '--{{prefix}}primary',
                        value: '#2185F4',
                    }
                );

                setting.theme_color('theme-color-blue');

                setting.sidebar_menu_style(
                    'sidebar-default navs-pill-all'
                );

                setting.page_layout('container-fluid');
                break;

            case 'sidebar-rounded-one':
                setting.setSettingColorChoice(
                    'theme_color',
                    {
                        key: '--{{prefix}}primary',
                        value: '#34A853',
                    }
                );

                setting.theme_color('theme-color-green');

                setting.sidebar_type([]);

                setting.sidebar_menu_style(
                    'sidebar-default navs-rounded'
                );

                setting.page_layout('container-fluid');
                break;

            case 'sidebar-rounded-all':
                setting.setSettingColorChoice(
                    'theme_color',
                    {
                        key: '--{{prefix}}primary',
                        value: '#553AF9',
                    }
                );

                setting.theme_color(
                    'theme-color-royal-blue'
                );

                setting.sidebar_type([]);

                setting.sidebar_menu_style(
                    'sidebar-default navs-rounded-all'
                );

                setting.page_layout('container-fluid');
                break;

            case 'rtl':
                setting.theme_scheme_direction('rtl');
                break;

            case 'dark-rtl':
                setting.theme_scheme('dark');
                setting.theme_scheme_direction('rtl');
                break;

            default:
                break;
        }
    }

    // --------------------------------------------------
    // Sidebar type
    // Reemplaza $(document).on(...)
    // --------------------------------------------------
    document.addEventListener('sidebar_type', (event) => {

        if (!window.IQSetting) {
            return;
        }

        const sidebarType =
            setting.options.setting.sidebar_type.value;

        const value = event.detail?.value ?? [];
        const currentValue =
            event.detail?.currentValue ?? '';

        if (value.length === 0) {
            return;
        }

        if (
            currentValue !== 'sidebar-mini' &&
            currentValue !== ''
        ) {

            if (
                sidebarType.includes('sidebar-hover') &&
                !value.includes('sidebar-mini')
            ) {

                const newTypes = [
                    ...sidebarType,
                    'sidebar-mini',
                ];

                setting.sidebar_type(newTypes);
            }
        }
    });

    // --------------------------------------------------
    // Navbar style
    // --------------------------------------------------
    document.addEventListener(
        'header_navbar',
        () => {

            if (!window.IQSetting) {
                return;
            }

            const headerNavbar =
                setting.options.setting.header_navbar;

            if (!headerNavbar) {
                return;
            }

            if (headerNavbar.value === 'nav-glass') {
                const elements = document.querySelectorAll(
                    headerNavbar.target
                );

                elements.forEach((element) => {
                    element.classList.add('navs-sticky');
                });
            }
        }
    );

    // --------------------------------------------------
    // Cerrar Customizer al hacer clic fuera
    // --------------------------------------------------
    document.addEventListener('click', (event) => {

        const liveCustomizerPanel =
            document.querySelector('#live-customizer');

        if (!liveCustomizerPanel) {
            return;
        }

        if (
            !liveCustomizerPanel.classList.contains('show')
        ) {
            return;
        }

        const clickedInsideCustomizer =
            event.target.closest('.live-customizer');

        const clickedSettingButton =
            event.target.closest('#settingbutton');

        const clickedButton =
            event.target.closest('.btn-check');

        if (
            !clickedInsideCustomizer &&
            !clickedSettingButton &&
            !clickedButton
        ) {

            if (
                typeof bootstrap !== 'undefined'
            ) {

                const instance =
                    bootstrap.Offcanvas.getInstance(
                        liveCustomizerPanel
                    );

                instance?.hide();
            }
        }
    });

    // --------------------------------------------------
    // Abrir Live Customizer por query string
    // --------------------------------------------------
    const liveCustomizer =
        IQUtils.getQueryString('live-customizer');

    if (
        liveCustomizer === 'open'
    ) {

        const liveCustomizerPanel =
            document.querySelector('#live-customizer');

        if (
            liveCustomizerPanel &&
            typeof bootstrap !== 'undefined'
        ) {

            const instance =
                bootstrap.Offcanvas.getOrCreateInstance(
                    liveCustomizerPanel
                );

            instance.show();
        }
    }

    // --------------------------------------------------
    // Reset Settings
    // --------------------------------------------------
    const resetSettings =
        document.querySelector(
            '[data-reset="settings"]'
        );

    if (resetSettings) {

        resetSettings.addEventListener(
            'click',
            (event) => {

                event.preventDefault();

                const confirmReset =
                    window.confirm(
                        'Are you sure you want to reset your settings?'
                    );

                if (confirmReset) {
                    window.IQSetting.reInit();
                }
            }
        );
    }

    return setting;
};

export default initSetting;
