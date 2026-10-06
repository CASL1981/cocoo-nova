const getSetting = () => window.IQSetting;

const sidebarInit = () => {
    const setting = getSetting();

    if (!setting?.options?.setting?.sidebar_type) {
        return;
    }

    const sidebarType = [
        ...setting.options.setting.sidebar_type.value,
    ];

    const sidebarResponsive =
        document.querySelector(
            '[data-sidebar="responsive"]'
        );

    if (window.innerWidth < 1025) {
        if (sidebarResponsive) {
            if (
                !sidebarResponsive.classList.contains(
                    'sidebar-mini'
                ) &&
                !sidebarType.includes('sidebar-mini')
            ) {
                sidebarType.push('sidebar-mini');
            }
        }
    }

    setting.sidebar_type(sidebarType);
};

const updateSidebarType = () => {
    const setting = getSetting();

    if (!setting?.options?.setting?.sidebar_type) {
        return;
    }

    const sidebarType = [
        ...setting.options.setting.sidebar_type.value,
    ];

    const index = sidebarType.indexOf(
        'sidebar-mini'
    );

    if (index !== -1) {
        sidebarType.splice(index, 1);
    } else {
        sidebarType.push('sidebar-mini');
    }

    setting.sidebar_type(sidebarType);
};

const initializeSidebar = () => {
    const sidebar =
        document.querySelector(
            '[data-toggle="main-sidebar"]'
        );

    if (!sidebar) {
        return;
    }

    // ========================================
    // Active menu
    // ========================================

    const activeItems =
        sidebar.querySelectorAll('.active');

    activeItems.forEach((element) => {
        element.classList.add('active');

        const parentList =
            element.closest('ul');

        if (
            !parentList ||
            parentList.classList.contains(
                'iq-main-menu'
            )
        ) {
            return;
        }

        const parentItem =
            parentList.closest('li');

        const parentLink =
            parentItem?.querySelector(
                ':scope > .nav-link'
            );

        if (!parentLink) {
            return;
        }

        parentLink.classList.add('active');

        if (window.bootstrap) {
            window.bootstrap.Collapse
                .getOrCreateInstance(
                    parentList,
                    {
                        toggle: true,
                    }
                )
                .show();
        }
    });

    // ========================================
    // Collapse events
    // ========================================

    sidebar
        .querySelectorAll('.collapse')
        .forEach((collapseElement) => {

            if (
                collapseElement.dataset.qompacSidebarInit ===
                'true'
            ) {
                return;
            }

            collapseElement.dataset.qompacSidebarInit =
                'true';

            collapseElement.addEventListener(
                'show.bs.collapse',
                () => {
                    collapseElement
                        .closest('li')
                        ?.classList.add('active');
                }
            );

            collapseElement.addEventListener(
                'hidden.bs.collapse',
                () => {
                    collapseElement
                        .closest('li')
                        ?.classList.remove('active');
                }
            );
        });

    const active =
        sidebar.querySelector('.active');

    active
        ?.closest('li')
        ?.classList.add('active');
};

const initializeSidebarToggle = () => {
    document
        .querySelectorAll(
            '[data-toggle="sidebar"]'
        )
        .forEach((button) => {

            if (
                button.dataset.qompacSidebarToggle ===
                'true'
            ) {
                return;
            }

            button.dataset.qompacSidebarToggle =
                'true';

            button.addEventListener(
                'click',
                () => {

                    const sidebar =
                        document.querySelector(
                            '.sidebar'
                        );

                    if (!sidebar) {
                        return;
                    }

                    const isMini =
                        sidebar.classList.contains(
                            'sidebar-mini'
                        );

                    sidebar.classList.toggle(
                        'sidebar-mini',
                        !isMini
                    );

                    updateSidebarType();
                }
            );
        });
};

const initSidebar = () => {
    sidebarInit();
    initializeSidebar();
    initializeSidebarToggle();
};

window.addEventListener(
    'resize',
    sidebarInit
);

document.addEventListener(
    'DOMContentLoaded',
    initSidebar
);

document.addEventListener(
    'livewire:navigated',
    initSidebar
);

window.QompacSidebar = {
    init: initSidebar,
};

export default initSidebar;
