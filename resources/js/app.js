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

// Campo en pesos colombianos (<x-forms.money>): "value" es el número que recibe Livewire
// (1234567.89, enlazado con x-modelable) y "display" lo que ve el usuario (1.234.567,89).
Alpine.data('moneyInput', ({ negative = false } = {}) => ({
    value: null,
    display: '',

    init() {
        this.display = this.format(this.value);

        // Valor que llega del servidor (al abrir o reiniciar el formulario): se vuelve a formatear,
        // salvo que ya coincida con lo escrito (evita mover lo que el usuario está tecleando).
        this.$watch('value', (value) => {
            if (this.normalize(this.parse(this.display)) !== this.normalize(value)) {
                this.display = this.format(value);
            }
        });
    },

    // "45000000.00" → "45000000": sin ceros decimales sobrantes, para comparar y mostrar.
    normalize(value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const [integer, decimals = ''] = String(value).split('.');
        const trimmed = decimals.replace(/0+$/, '');

        return trimmed ? `${integer}.${trimmed}` : integer;
    },

    // Texto del usuario ("-1.234.567,5") → número sin formato ("-1234567.5"), o null si está vacío.
    parse(text) {
        const sign = negative && String(text).trim().startsWith('-') ? '-' : '';
        const [integer, ...rest] = String(text).replace(/[^\d,]/g, '').split(',');
        const digits = integer.replace(/^0+(?=\d)/, '');
        const decimals = rest.join('').slice(0, 2);

        if (digits === '' && decimals === '') {
            return null;
        }

        return `${sign}${digits || '0'}${decimals ? `.${decimals}` : ''}`;
    },

    // Número → "1.234.567,5" (punto de miles y coma decimal, formato colombiano).
    format(value) {
        const normalized = this.normalize(value);

        if (normalized === null) {
            return '';
        }

        const sign = normalized.startsWith('-') ? '-' : '';
        const [integer, decimals] = normalized.replace('-', '').split('.');

        return `${sign}${integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}${decimals ? `,${decimals}` : ''}`;
    },

    // Al escribir: actualiza el número y reformatea conservando la coma y los decimales tecleados.
    sync(event) {
        const text = this.display;
        const raw = this.parse(text);
        const comma = text.indexOf(',');
        let formatted = raw === null ? '' : this.format(raw.split('.')[0]);

        if (comma !== -1) {
            formatted = `${formatted || '0'},${text.slice(comma + 1).replace(/\D/g, '').slice(0, 2)}`;
        } else if (raw === null && negative && text.trim() === '-') {
            formatted = '-';
        }

        this.value = raw;
        this.display = formatted;
        this.$nextTick(() => event.target.setSelectionRange(formatted.length, formatted.length));
    },
}));

// Tooltip Bootstrap declarativo: x-tooltip="'Texto'" (ubicación opcional: x-tooltip.bottom;
// x-tooltip.wide para párrafos largos, ver .tooltip-wide en app.css).
// Se crea y destruye con el elemento, así no quedan tooltips huérfanos tras los re-renderizados
// de Livewire. Uso habitual a través del componente Blade <x-tooltip>.
Alpine.directive('tooltip', (el, { modifiers, expression }, { evaluate, cleanup }) => {
    const tooltip = new bootstrap.Tooltip(el, {
        title: evaluate(expression),
        placement: modifiers.find((modifier) => ['top', 'bottom', 'left', 'right'].includes(modifier)) ?? 'top',
        trigger: modifiers.includes('wide') ? 'hover focus' : 'hover',
        customClass: modifiers.includes('wide') ? 'tooltip-wide' : '',
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
