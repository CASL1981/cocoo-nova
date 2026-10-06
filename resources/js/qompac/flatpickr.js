const initFlatpickr = (root = document) => {
    if (typeof window.flatpickr !== 'function') {
        return;
    }

    const initialize = (selector, options) => {
        root.querySelectorAll(selector).forEach((element) => {
            if (element._flatpickr) {
                return;
            }

            window.flatpickr(element, options);
        });
    };

    // ========================================
    // plugins/flatpickr.js original
    // ========================================

    initialize('.flatpickr_datetime', {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
    });

    initialize('.flatpickr_humandate', {
        altInput: true,
        altFormat: 'F j, Y',
        dateFormat: 'Y-m-d',
    });

    initialize('.flatpickr_minDate', {
        minDate: '2020-01',
    });

    initialize('.flatpickr_maxDate', {
        dateFormat: 'd.m.Y',
        maxDate: '15.12.2017',
    });

    initialize('.flatpickr_specificdisable', {
        disable: [
            '2025-01-30',
            '2025-02-21',
            '2025-03-08',
            new Date(2025, 4, 9),
        ],
        dateFormat: 'Y-m-d',
    });

    initialize('.flatpickr_disableexcept', {
        enable: [
            '2025-03-30',
            '2025-05-21',
            '2025-06-08',
            new Date(2025, 8, 9),
        ],
    });

    initialize('.flatpickr_multidate', {
        mode: 'multiple',
        dateFormat: 'Y-m-d',
    });

    initialize('.flatpickrrange', {
        mode: 'range',
        minDate: 'today',
        dateFormat: 'Y-m-d',
    });

    initialize('.flatpickrdate', {
        minDate: 'today',
        dateFormat: 'Y-m-d',
    });

    initialize('.flatpickrtime', {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
    });

    // ========================================
    // Flatpickr que estaba dentro de qompac-ui.js
    // ========================================

    initialize('.date_flatpicker', {
        minDate: 'today',
        dateFormat: 'Y-m-d',
    });

    initialize('.range_flatpicker', {
        mode: 'range',
        minDate: 'today',
        dateFormat: 'Y-m-d',
    });

    initialize('.wrap_flatpicker', {
        wrap: true,
        minDate: 'today',
        dateFormat: 'Y-m-d',
    });

    initialize('.time_flatpicker', {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
    });

    initialize('.inline_flatpickr', {
        inline: true,
        minDate: 'today',
        dateFormat: 'Y-m-d',
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initFlatpickr();
});

document.addEventListener('livewire:navigated', () => {
    initFlatpickr();
});

export default initFlatpickr;
