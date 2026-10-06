const getBootstrap = () => window.bootstrap;

const initLoader = () => {
    const loader = document.querySelector('.loader');

    if (!loader) {
        return;
    }

    loader.classList.add(
        'animate__animated',
        'animate__fadeOut'
    );

    window.setTimeout(() => {
        loader.classList.add('d-none');
    }, 200);
};

const initStickyNavbar = () => {
    window.addEventListener('scroll', () => {
        const navbar =
            document.querySelector('.navs-sticky');

        if (!navbar) {
            return;
        }

        if (document.documentElement.scrollTop >= 100) {
            navbar.classList.add('menu-sticky');
        } else {
            navbar.classList.remove('menu-sticky');
        }
    });
};

const initBootstrapComponents = () => {
    const bootstrap = getBootstrap();

    if (!bootstrap) {
        return;
    }

    document
        .querySelectorAll('[data-bs-toggle="popover"]')
        .forEach((element) => {
            bootstrap.Popover.getOrCreateInstance(element);
        });

    document
        .querySelectorAll(
            '[data-bs-toggle="tooltip"], [data-sidebar-toggle="tooltip"]'
        )
        .forEach((element) => {
            bootstrap.Tooltip.getOrCreateInstance(element);
        });
};

const initProgressBars = () => {
    document
        .querySelectorAll('[data-toggle="progress-bar"]')
        .forEach((element) => {
            const currentValue =
                element.getAttribute('aria-valuenow');

            if (!currentValue) {
                return;
            }

            element.style.width = '0%';
            element.style.transition = 'width 2s';

            if (typeof window.Waypoint !== 'undefined') {
                new window.Waypoint({
                    element,
                    handler() {
                        window.setTimeout(() => {
                            element.style.width =
                                `${currentValue}%`;
                        }, 100);
                    },
                    offset: 'bottom-in-view',
                });
            } else {
                element.style.width =
                    `${currentValue}%`;
            }
        });
};

const initClipboard = () => {
    document
        .querySelectorAll('[data-toggle="copy"]')
        .forEach((element) => {
            element.addEventListener('click', async () => {
                const target =
                    element.getAttribute(
                        'data-copy-target'
                    );

                let value =
                    element.getAttribute(
                        'data-copy-value'
                    );

                const container = target
                    ? document.querySelector(target)
                    : null;

                if (container) {
                    value =
                        'value' in container &&
                        container.value !== ''
                            ? container.value
                            : container.innerHTML;
                }

                if (value == null) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(
                        value
                    );
                } catch {
                    const textarea =
                        document.createElement('textarea');

                    textarea.value = value;

                    document.body.appendChild(
                        textarea
                    );

                    textarea.select();

                    document.execCommand('copy');

                    textarea.remove();
                }

                const bootstrap = getBootstrap();

                if (!bootstrap) {
                    return;
                }

                const tooltip =
                    bootstrap.Tooltip.getInstance(
                        element
                    );

                if (!tooltip) {
                    return;
                }

                element.setAttribute(
                    'data-bs-original-title',
                    'Copied!'
                );

                tooltip.show();

                window.setTimeout(() => {
                    element.setAttribute(
                        'data-bs-original-title',
                        'Copy'
                    );

                    tooltip.hide();
                }, 500);
            });
        });
};

const initQuantityButtons = () => {
    const updateQuantity = (element, amount) => {
        const container =
            element.closest('[data-qty="btn"]');

        if (!container) {
            return;
        }

        const input =
            container.querySelector(
                '[data-qty="input"]'
            );

        if (!input) {
            return;
        }

        const oldValue =
            Number(input.value) || 0;

        const newValue =
            oldValue + Number(amount);

        if (newValue >= 1) {
            input.value = newValue;
            input.dispatchEvent(
                new Event('change', {
                    bubbles: true,
                })
            );
        }
    };

    document
        .querySelectorAll('.iq-quantity-plus')
        .forEach((element) => {
            element.addEventListener('click', () => {
                updateQuantity(element, 1);
            });
        });

    document
        .querySelectorAll('.iq-quantity-minus')
        .forEach((element) => {
            element.addEventListener('click', () => {
                updateQuantity(element, -1);
            });
        });
};

const initCounterUp = () => {
    if (
        typeof window.counterUp === 'undefined' ||
        typeof window.Waypoint === 'undefined'
    ) {
        return;
    }

    const counterUp =
        window.counterUp.default;

    document
        .querySelectorAll('.counter')
        .forEach((element) => {
            const waypoint =
                new window.Waypoint({
                    element,
                    handler() {
                        counterUp(element, {
                            duration: 1000,
                            delay: 10,
                        });

                        waypoint.destroy();
                    },
                    offset: 'bottom-in-view',
                });
        });
};

const initDataTables = () => {
    const $ = window.jQuery;

    if (!$ || !$.fn?.DataTable) {
        return;
    }

    $('[data-toggle="data-table"]').each(
        function () {
            if ($.fn.DataTable.isDataTable(this)) {
                return;
            }

            $(this).DataTable({
                autoWidth: false,
                dom:
                    '<"row align-items-center"' +
                    '<"col-md-6" l>' +
                    '<"col-md-6" f>>' +
                    '<"table-responsive my-3" rt>' +
                    '<"row align-items-center"' +
                    '<"col-md-6" i>' +
                    '<"col-md-6" p>>' +
                    '<"clear">',
            });
        }
    );

    $('[data-toggle="data-table-column-hidden"]').each(
        function () {
            if ($.fn.DataTable.isDataTable(this)) {
                return;
            }

            const table =
                $(this).DataTable({});

            $(document)
                .off(
                    'click.qompac',
                    'a.toggle-vis'
                )
                .on(
                    'click.qompac',
                    'a.toggle-vis',
                    function (event) {
                        event.preventDefault();

                        const column =
                            table.column(
                                $(this).attr(
                                    'data-column'
                                )
                            );

                        column.visible(
                            !column.visible()
                        );
                    }
                );
        }
    );

    $('[data-toggle="data-table-column-filter"]').each(
        function () {
            const tableElement = this;

            if ($.fn.DataTable.isDataTable(
                tableElement
            )) {
                return;
            }

            $(tableElement)
                .find('tfoot th')
                .each(function () {
                    const title =
                        $(this).attr('title') ||
                        '';

                    $(this).html(
                        `<input type="text"
                            class="form-control form-control-sm"
                            placeholder="${title}" />`
                    );
                });

            $(tableElement).DataTable({
                initComplete() {
                    this.api()
                        .columns()
                        .every(function () {
                            const column = this;

                            $('input', column.footer())
                                .on(
                                    'keyup change clear',
                                    function () {
                                        if (
                                            column.search() !==
                                            this.value
                                        ) {
                                            column
                                                .search(
                                                    this.value
                                                )
                                                .draw();
                                        }
                                    }
                                );
                        });
                },
            });
        }
    );

    const languageSelector =
        document.querySelector(
            '#langSelector'
        );

    const languageTable =
        document.querySelector(
            '[data-toggle="data-table-multi-language"]'
        );

    if (
        languageSelector &&
        languageTable &&
        !$.fn.DataTable.isDataTable(
            languageTable
        )
    ) {
        const getLanguage = () =>
            Array.from(
                languageSelector.options
            )
                .filter(
                    (option) => option.selected
                )
                .map(
                    (option) =>
                        option.getAttribute(
                            'data-path'
                        )
                );

        const initialize = () => {
            $(languageTable).DataTable({
                language: {
                    url: getLanguage(),
                },
            });
        };

        initialize();

        languageSelector.addEventListener(
            'change',
            () => {
                if (
                    $.fn.DataTable.isDataTable(
                        languageTable
                    )
                ) {
                    $(languageTable)
                        .DataTable()
                        .destroy();
                }

                initialize();
            }
        );
    }
};

const initPricingTable = () => {
    const table =
        document.querySelector('#my-table');

    if (!table) {
        return;
    }

    table
        .querySelectorAll('tr th')
        .forEach((header) => {
            header.addEventListener(
                'click',
                () => {
                    table
                        .querySelectorAll(
                            'tr th > *'
                        )
                        .forEach((element) => {
                            element.classList.remove(
                                'active'
                            );
                        });

                    header.children[0]
                        ?.classList.add('active');

                    table
                        .querySelectorAll('td')
                        .forEach((element) => {
                            element.classList.remove(
                                'active'
                            );
                        });

                    const headers =
                        Array.from(
                            header.parentElement
                                .children
                        );

                    const index =
                        headers.indexOf(
                            header
                        ) + 1;

                    table
                        .querySelectorAll(
                            `tr td:nth-child(${index})`
                        )
                        .forEach((element) => {
                            element.classList.add(
                                'active'
                            );
                        });
                }
            );
        });

    const checkbox =
        document.querySelector(
            '#contcheckbox'
        );

    if (checkbox) {
        const update = () => {
            document
                .querySelectorAll(
                    '.montlypricing'
                )
                .forEach((element) => {
                    element.style.display =
                        checkbox.checked
                            ? 'none'
                            : 'flex';
                });

            document
                .querySelectorAll(
                    '.yearlypricing'
                )
                .forEach((element) => {
                    element.style.display =
                        checkbox.checked
                            ? 'flex'
                            : 'none';
                });
        };

        checkbox.addEventListener(
            'change',
            update
        );

        update();
    }
};

const initResizePlugins = () => {
    const tabs =
        document.querySelectorAll('.nav');

    tabs.forEach((element) => {
        if (window.innerWidth < 1025) {
            if (
                !element.classList.contains(
                    'flex-column'
                ) &&
                element.classList.contains(
                    'nav-tabs'
                ) &&
                element.classList.contains(
                    'nav-pills'
                )
            ) {
                element.classList.add(
                    'flex-column',
                    'on-resize'
                );
            }
        } else if (
            element.classList.contains(
                'on-resize'
            )
        ) {
            element.classList.remove(
                'flex-column',
                'on-resize'
            );
        }
    });
};

const initBackToTop = () => {
    const button =
        document.querySelector(
            '#back-to-top'
        );

    const top =
        document.querySelector('#top');

    if (!button || !top) {
        return;
    }

    button.classList.add(
        'animate__animated',
        'animate__fadeOut'
    );

    window.addEventListener(
        'scroll',
        () => {
            if (
                document.documentElement
                    .scrollTop > 250
            ) {
                button.classList.remove(
                    'animate__fadeOut'
                );

                button.classList.add(
                    'animate__fadeIn'
                );
            } else {
                button.classList.remove(
                    'animate__fadeIn'
                );

                button.classList.add(
                    'animate__fadeOut'
                );
            }
        }
    );

    top.addEventListener(
        'click',
        (event) => {
            event.preventDefault();

            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        }
    );
};

const initOffcanvas = () => {
    const bootstrap = getBootstrap();

    if (!bootstrap) {
        return;
    }

    const darkenScreen = (visible) => {
        document
            .querySelector(
                '.screen-darken'
            )
            ?.classList.toggle(
                'active',
                visible
            );
    };

    const closeOffcanvas = () => {
        darkenScreen(false);

        document
            .querySelector(
                '.mobile-offcanvas.show'
            )
            ?.classList.remove('show');

        document.body.classList.remove(
            'offcanvas-active'
        );
    };

    const showOffcanvas = (id) => {
        darkenScreen(true);

        document
            .getElementById(id)
            ?.classList.add('show');

        document.body.classList.add(
            'offcanvas-active'
        );
    };

    document
        .querySelectorAll('[data-trigger]')
        .forEach((element) => {
            element.addEventListener(
                'click',
                (event) => {
                    event.preventDefault();

                    showOffcanvas(
                        element.getAttribute(
                            'data-trigger'
                        )
                    );
                }
            );
        });

    document
        .querySelectorAll('.btn-close')
        .forEach((element) => {
            element.addEventListener(
                'click',
                closeOffcanvas
            );
        });

    document
        .querySelector('.screen-darken')
        ?.addEventListener(
            'click',
            closeOffcanvas
        );

    document
        .querySelector('#navbarSideCollapse')
        ?.addEventListener(
            'click',
            () => {
                document
                    .querySelector(
                        '.offcanvas-collapse'
                    )
                    ?.classList.toggle('open');
            }
        );
};

const initSearch = () => {
    document
        .querySelectorAll('.search-full')
        .forEach((element) => {
            element.addEventListener(
                'click',
                () => {
                    document
                        .querySelector(
                            '.search-mini'
                        )
                        ?.classList.toggle(
                            'active'
                        );
                }
            );
        });
};

const initFullscreen = () => {
    document
        .querySelectorAll('.iq-full-screen')
        .forEach((button) => {

            if (
                button.dataset.qompacFullscreenInit === 'true'
            ) {
                return;
            }

            button.dataset.qompacFullscreenInit = 'true';

            button.addEventListener(
                'click',
                async (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    const normalScreen =
                        button.querySelector('.normal-screen');

                    const fullScreen =
                        button.querySelector('.full-normal-screen');

                    if (!normalScreen || !fullScreen) {
                        return;
                    }

                    try {
                        if (!document.fullscreenElement) {
                            await document.documentElement.requestFullscreen();
                        } else {
                            await document.exitFullscreen();
                        }
                    } catch (error) {
                        console.warn(
                            'Qompac Fullscreen:',
                            error
                        );

                        return;
                    }

                    const isFullscreen =
                        document.fullscreenElement !== null;

                    normalScreen.classList.toggle(
                        'd-none',
                        isFullscreen
                    );

                    fullScreen.classList.toggle(
                        'd-none',
                        !isFullscreen
                    );
                }
            );
        });

    document.addEventListener(
        'fullscreenchange',
        () => {
            document
                .querySelectorAll('.iq-full-screen')
                .forEach((button) => {

                    const normalScreen =
                        button.querySelector('.normal-screen');

                    const fullScreen =
                        button.querySelector('.full-normal-screen');

                    if (!normalScreen || !fullScreen) {
                        return;
                    }

                    const isFullscreen =
                        document.fullscreenElement !== null;

                    normalScreen.classList.toggle(
                        'd-none',
                        isFullscreen
                    );

                    fullScreen.classList.toggle(
                        'd-none',
                        !isFullscreen
                    );
                });
        }
    );
};

const initFontSize = () => {
    const sizes =
        document.querySelectorAll(
            '[data-change="fs"]'
        );

    sizes.forEach((button) => {
        button.addEventListener(
            'click',
            () => {
                const size =
                    button.dataset.size;

                sizes.forEach(
                    (element) => {
                        element.classList.remove(
                            'btn-primary'
                        );
                    }
                );

                const html =
                    document.documentElement;

                if (
                    html.style.fontSize !==
                    size
                ) {
                    html.style.fontSize =
                        size;

                    button.classList.add(
                        'btn-primary'
                    );
                } else {
                    html.style.removeProperty(
                        'font-size'
                    );
                }

                window.dispatchEvent(
                    new Event('resize')
                );

                hideTooltips();
            }
        );
    });
};

const hideTooltips = () => {
    const bootstrap = getBootstrap();

    if (!bootstrap) {
        return;
    }

    document
        .querySelectorAll(
            '[data-bs-toggle="tooltip"]'
        )
        .forEach((element) => {
            bootstrap.Tooltip
                .getInstance(element)
                ?.hide();
        });
};

const initHeaderToggle = () => {
    const bootstrap = getBootstrap();

    if (!bootstrap) {
        return;
    }

    const configurations = [
        {
            collapse: '#navbarSupportedContent',
            offcanvas: '#offcanvasBottom',
        },
        {
            collapse: '#navbarSupportedContent1',
            offcanvas: '#offcanvasBottom1',
        },
    ];

    configurations.forEach(
        ({ collapse, offcanvas }) => {
            const collapseElement =
                document.querySelector(
                    collapse
                );

            const offcanvasElement =
                document.querySelector(
                    offcanvas
                );

            if (
                !collapseElement ||
                !offcanvasElement
            ) {
                return;
            }

            const offcanvasInstance =
                bootstrap.Offcanvas
                    .getOrCreateInstance(
                        offcanvasElement
                    );

            collapseElement.addEventListener(
                'show.bs.collapse',
                () => {
                    offcanvasInstance.show();

                    document
                        .querySelector(
                            '.offcanvas-backdrop'
                        )
                        ?.addEventListener(
                            'click',
                            () => {
                                bootstrap.Collapse
                                    .getOrCreateInstance(
                                        collapseElement
                                    )
                                    .hide();
                            },
                            { once: true }
                        );
                }
            );

            collapseElement.addEventListener(
                'hide.bs.collapse',
                () => {
                    offcanvasInstance.hide();
                }
            );
        }
    );
};

const initSidebarActiveBorder = () => {
    const sidebar =
        document.querySelector(
            '[data-toggle="main-sidebar"]'
        );

    if (!sidebar) {
        return;
    }

    sidebar
        .querySelectorAll('.collapse')
        .forEach((collapse) => {
            collapse.addEventListener(
                'show.bs.collapse',
                () => {
                    collapse
                        .closest('li')
                        ?.classList.add(
                            'active'
                        );
                }
            );

            collapse.addEventListener(
                'hidden.bs.collapse',
                () => {
                    collapse
                        .closest('li')
                        ?.classList.remove(
                            'active'
                        );
                }
            );
        });

    const active =
        sidebar.querySelector('.active');

    active
        ?.closest('li')
        ?.classList.add('active');
};

const initResetSettings = () => {
    document
        .querySelectorAll(
            '[data-reset="settings"]'
        )
        .forEach((element) => {
            element.addEventListener(
                'click',
                (event) => {
                    event.preventDefault();

                    if (
                        !window.confirm(
                            'Are you sure you want to reset your settings?'
                        )
                    ) {
                        return;
                    }

                    window.IQSetting?.reInit();
                }
            );
        });
};

const initCopySettings = () => {
    document
        .querySelectorAll(
            '[data-copy="settings"]'
        )
        .forEach((element) => {
            element.addEventListener(
                'click',
                async (event) => {
                    event.preventDefault();

                    if (
                        !window.IQSetting
                    ) {
                        return;
                    }

                    const value =
                        window.IQSetting
                            .getSettingJson();

                    try {
                        await navigator.clipboard
                            .writeText(value);
                    } catch {
                        const textarea =
                            document.createElement(
                                'textarea'
                            );

                        textarea.value = value;

                        document.body.appendChild(
                            textarea
                        );

                        textarea.select();

                        document.execCommand(
                            'copy'
                        );

                        textarea.remove();
                    }

                    const bootstrap =
                        getBootstrap();

                    const tooltip =
                        bootstrap?.Tooltip
                            .getInstance(
                                element
                            );

                    tooltip?.show();

                    window.setTimeout(() => {
                        tooltip?.hide();
                    }, 500);
                }
            );
        });
};

const initStatus = () => {
    document
        .querySelectorAll('.iq-status')
        .forEach((checkbox) => {
            checkbox.addEventListener(
                'change',
                () => {
                    document
                        .querySelectorAll(
                            '.iq-reset-status'
                        )
                        .forEach((element) => {
                            element.textContent =
                                checkbox.checked
                                    ? 'Online'
                                    : 'Offline';
                        });
                }
            );
        });
};

const initSweetAlerts = () => {
    if (
        typeof window.Swal ===
        'undefined'
    ) {
        return;
    }

    document
        .querySelectorAll('.delete-btn')
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    window.Swal.fire({
                        title: 'Are you sure?',
                        text: 'You want to delete this item',
                        icon: 'error',
                        showCancelButton: true,
                        backdrop:
                            'rgba(60,60,60,0.8)',
                        confirmButtonText:
                            'Yes, delete it!',
                        confirmButtonColor:
                            '#c03221',
                    }).then((result) => {
                        if (
                            !result.isConfirmed
                        ) {
                            return;
                        }

                        button
                            .closest(
                                '[data-item="list"]'
                            )
                            ?.remove();

                        window.Swal.fire(
                            'Deleted!',
                            'Your item has been deleted.',
                            'success'
                        );
                    });
                }
            );
        });

    document
        .querySelectorAll('.restore-btn')
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    window.Swal.fire({
                        title: 'Are you sure?',
                        text: 'You want to restore this item',
                        icon: 'info',
                        showCancelButton: true,
                        backdrop:
                            'rgba(60,60,60,0.8)',
                        confirmButtonText:
                            'Yes',
                        confirmButtonColor:
                            '#c03221',
                    }).then((result) => {
                        if (
                            !result.isConfirmed
                        ) {
                            return;
                        }

                        button
                            .closest(
                                '[data-item="list"]'
                            )
                            ?.remove();

                        window.Swal.fire(
                            'Restore!',
                            'Your item has been restore.',
                            'success'
                        );
                    });
                }
            );
        });

    document
        .querySelectorAll('.wishlist-btn')
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    window.Swal.fire(
                        'Added!',
                        'Your item has been Added to the wishlist.',
                        'success'
                    );
                }
            );
        });

    document
        .querySelectorAll('.cart-btn')
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    window.Swal.fire(
                        'Added!',
                        'Your item has been Added to the cart.',
                        'success'
                    );
                }
            );
        });
};

const initFormValidation = () => {
    document
        .querySelectorAll(
            '.needs-validation'
        )
        .forEach((form) => {
            form.addEventListener(
                'submit',
                (event) => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add(
                        'was-validated'
                    );
                }
            );
        });
};

const initializeQompac = () => {
    initLoader();
    initStickyNavbar();
    initBootstrapComponents();
    initProgressBars();
    initClipboard();
    initQuantityButtons();
    initCounterUp();

    /*
     * NO se inicializan aquí:
     *
     * Flatpickr
     * SliderTab
     * WidgetCharts
     * DashboardCharts
     *
     * porque ya están separados en módulos
     * independientes de Vite.
     */

    initDataTables();
    initPricingTable();
    initResizePlugins();
    initBackToTop();
    initOffcanvas();
    initSearch();
    initFullscreen();
    initFontSize();
    initHeaderToggle();
    initSidebarActiveBorder();
    initResetSettings();
    initCopySettings();
    initStatus();
    initSweetAlerts();
    initFormValidation();
};

/*
 * ============================================================
 * Ciclo de vida compatible con Livewire + wire:navigate
 * ============================================================
 */

let qompacInitialPageInitialized = false;

const initializeInitialPage = () => {
    if (qompacInitialPageInitialized) {
        return;
    }

    qompacInitialPageInitialized = true;
    initializeQompac();
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        initializeInitialPage,
        { once: true }
    );
} else {
    initializeInitialPage();
}

document.addEventListener(
    'livewire:navigated',
    () => {
        /*
         * La primera navegación puede producirse durante
         * la carga inicial de Livewire.
         *
         * Si Qompac todavía no fue inicializado, hacerlo
         * ahora y evitar una segunda inicialización.
         */
        if (!qompacInitialPageInitialized) {
            initializeInitialPage();
            return;
        }

        /*
         * A partir de aquí son navegaciones SPA reales.
         */
        initializeQompac();
    }
);

export default initializeQompac;
