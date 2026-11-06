import {
    Livewire,
    Alpine
} from '../../vendor/livewire/livewire/dist/livewire.esm';

// ========================================
// css de qompac-ui
// ========================================

// import '../css/qompac-ui.css';
// import '../css/datatables-qompac.css';

// ========================================
// librerias de terceros
// ========================================

import $ from 'jquery';

window.$ = $;
window.jQuery = $;

import DataTable from 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';

DataTable.use(window.jQuery);

window.DataTable = DataTable;

import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

import * as Popper from '@popperjs/core'; //  Solución recomendada
window.Popper = Popper;

import select2 from 'select2';

select2(window.jQuery);

import _ from 'lodash';

window._ = _;

import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

window.flatpickr = flatpickr;

import Swiper from 'swiper';

window.Swiper = Swiper;

import Scrollbar from 'smooth-scrollbar';

window.Scrollbar = Scrollbar;

import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

// ========================================
// Qompac dependencias desde resources/js/qompac
// ========================================

import iqUtils from './qompac/utility';
import IQSetting from './qompac/setting';
import initSetting from './qompac/setting-init';

import initFlatpickr from './qompac/flatpickr';
import sliderTab from './qompac/slider-tabs';
import initSwiperSliders from './qompac/swiper-slider';
import initWidgetCharts from './qompac/widgetcharts';
import initDashboard from './qompac/dashboard';
import initializeQompac from './qompac/qompac-ui';
import initSidebar from './qompac/sidebar';
import 'bootstrap-icons/font/bootstrap-icons.css';

// ========================================
// Qompac
// ========================================

sliderTab.initializeAll();

initSwiperSliders();

initSetting();


function initializeQompacAfterNavigation() {
    initSidebar();
    initFlatpickr();
    initWidgetCharts();
    initDashboard();

    window.IQSetting?.refreshListeners();

    requestAnimationFrame(() => {
        syncCustomizerRadios();
    });
}

function syncCustomizerRadios() {
    const settings = window.IQSetting?.options?.setting;

    if (!settings) {
        return;
    }

    Object.entries(settings).forEach(
        ([key, option]) => {
            if (!option || Array.isArray(option.value)) {
                return;
            }

            document
                .querySelectorAll(
                    `input[name="${key}"]`
                )
                .forEach((input) => {
                    input.checked =
                        input.value === option.value;
                });
        }
    );

    const sidebarTypes =
        settings.sidebar_type?.value ?? [];

    document
        .querySelectorAll(
            'input[name="sidebar_type"]'
        )
        .forEach((input) => {
            input.checked =
                sidebarTypes.includes(input.value);
        });
}

document.addEventListener('livewire:navigated', () => {
    initializeQompacAfterNavigation();
});

// ========================================
// Livewire
// ========================================

document.addEventListener('livewire:navigated', () => {
    if (!window.IQSetting) {
        return;
    }

    const settings = window.IQSetting.options?.setting;

    if (!settings) {
        return;
    }

    const root = document.documentElement;

    // Tema claro / oscuro
    if (settings.theme_scheme?.value) {
        root.setAttribute(
            'data-bs-theme',
            settings.theme_scheme.value
        );
    }

    // Color del tema
    if (settings.theme_color?.value) {
        root.setAttribute(
            'data-bs-theme-color',
            settings.theme_color.value
        );
    }

    // Dirección
    if (settings.theme_scheme_direction?.value) {
        root.setAttribute(
            'dir',
            settings.theme_scheme_direction.value
        );
    }

    // Tamaño de fuente
    if (settings.theme_font_size?.value) {
        root.classList.remove(
            'theme-fs-sm',
            'theme-fs-md',
            'theme-fs-lg'
        );

        root.classList.add(
            settings.theme_font_size.value
        );
    }
});

// Tooltip Bootstrap declarativo: x-tooltip="'Texto'" (ubicación opcional: x-tooltip.bottom).
// Se crea y destruye con el elemento, así no quedan tooltips huérfanos tras los re-renderizados
// de Livewire. Uso habitual a través del componente Blade <x-tooltip>.
Alpine.directive('tooltip', (el, { modifiers, expression }, { evaluate, cleanup }) => {
    const tooltip = new bootstrap.Tooltip(el, {
        title: evaluate(expression),
        placement: modifiers[0] ?? 'top',
        trigger: 'hover',
    });

    // Al abrir un modal o una confirmación el puntero no sale del botón: se oculta al hacer clic.
    const hide = () => tooltip.hide();
    el.addEventListener('click', hide);

    cleanup(() => {
        el.removeEventListener('click', hide);
        tooltip.dispose();
    });
});

// Texto completo de celdas recortadas: si el contenido de una celda de tabla no cabe
// (ancho fijo con "…"), al pasar el mouse se muestra en un tooltip como el de los botones.
// Delegado en document: aplica a todas las tablas, incluidas las que Livewire re-renderiza.
// Solo hay un tooltip de celda a la vez; se descarta al salir, al desplazarse o si Livewire
// reemplaza la celda (al siguiente movimiento del mouse).
let cellTooltip = null;

const hideCellTooltip = () => {
    cellTooltip?.tooltip.dispose();
    cellTooltip = null;
};

document.addEventListener('mouseover', (event) => {
    const cell = event.target.closest?.('.table td, .table th') ?? null;

    if (cellTooltip && (cellTooltip.cell !== cell || ! cellTooltip.cell.isConnected)) {
        hideCellTooltip();
    }

    if (! cell || cellTooltip || cell.querySelector('input, select, textarea, button, a, .dropdown-menu')) {
        return;
    }

    if (cell.scrollWidth <= cell.clientWidth + 1) {
        return;
    }

    const text = cell.textContent.replace(/\s+/g, ' ').trim();

    if (text === '') {
        return;
    }

    const tooltip = new bootstrap.Tooltip(cell, { title: text, placement: 'top', trigger: 'manual', container: 'body' });
    cellTooltip = { cell, tooltip };
    tooltip.show();
});

document.addEventListener('scroll', hideCellTooltip, { capture: true, passive: true });

Livewire.start();

// ========================================
// Librerías de terceros sweetalert2
// ========================================

import Swal from 'sweetalert2';

window.confirmAction = function ({
    title = '¿Estás seguro?',
    text = 'Esta acción no se puede deshacer.',
    icon = 'warning',
    confirmButtonText = 'Sí, continuar',
    cancelButtonText = 'Cancelar',
} = {}) {
    return Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        focusCancel: true,
    }).then((result) => {
        if (! result.isConfirmed) {
            return Promise.reject('cancelled');
        }
    });
};
