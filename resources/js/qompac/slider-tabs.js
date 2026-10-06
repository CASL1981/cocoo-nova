class SliderTab {
    constructor() {
        this.instances = new WeakMap();
    }

    init(element, option = undefined) {
        if (!(element instanceof HTMLElement)) {
            return;
        }

        // Evita inicializar dos veces el mismo elemento
        if (this.instances.has(element)) {
            return this.instances.get(element);
        }

        const firstLink = element.querySelector(
            '.nav-item:first-child .nav-link'
        );

        if (!firstLink) {
            return;
        }

        const slider = document.createElement('div');
        const sliderThumb = 'nav-slider-thumb';
        const elemClass = 'nav-slider';

        const tab = firstLink.cloneNode(false);

        tab.innerHTML = '';

        slider.classList.add(
            sliderThumb,
            'position-absolute',
            'nav-link'
        );

        element.classList.add(elemClass);

        slider.appendChild(tab);
        element.appendChild(slider);

        const instance = {
            element,
            slider,
            option,
            sliderThumb,
            elemClass,
            resizeHandler: null,
            observer: null,
        };

        this.instances.set(element, instance);

        this.resetSlider(instance);
        this.mouseClick(instance);
        this.resize(instance);
        this.updateRTL(instance);

        return instance;
    }

    resetSlider(instance) {
        const { element, slider } = instance;
        const firstItem = element.querySelector(
            '.nav-item:first-child'
        );

        if (!firstItem) {
            return;
        }

        slider.style.padding = '0px';
        slider.style.width = `${firstItem.offsetWidth}px`;
        slider.style.height = `${firstItem.offsetHeight}px`;
        slider.style.transform = 'translate3d(0px, 0px, 0px)';
        slider.style.transition = '300ms ease-in-out';
    }

    mouseClick(instance) {
        const { element } = instance;

        element.addEventListener('click', (event) => {
            const target = event.target.closest('.nav-link');
            const item = target?.closest('.nav-item');

            if (!item) {
                return;
            }

            const items = Array.from(
                element.querySelectorAll('.nav-item')
            );

            const index = items.indexOf(item) + 1;

            this.updateSlide(
                item,
                items,
                index,
                instance
            );
        });
    }

    updateSlide(item, items, index, instance, callback = null) {
        const { element, slider } = instance;

        const previousItems = element.querySelectorAll(
            '.nav-item'
        );

        previousItems.forEach((navItem) => {
            navItem
                .querySelector('.nav-link')
                ?.classList.remove('active');
        });

        let sum = 0;

        if (element.classList.contains('flex-column')) {
            for (let i = 0; i < items.indexOf(item); i++) {
                sum += items[i].offsetHeight;
            }

            slider.style.transform =
                `translate3d(0px, ${sum}px, 0px)`;

            const currentItem = items[index - 1];

            if (!currentItem) {
                return;
            }

            currentItem
                .querySelector('.nav-link')
                ?.classList.add('active');

            slider.style.height =
                `${currentItem.offsetHeight}px`;

            slider.style.width = '100%';

            callback?.(currentItem);

            return;
        }

        for (let i = 0; i < items.indexOf(item); i++) {
            sum += items[i].offsetWidth;
        }

        const isRtl =
            document.documentElement.getAttribute('dir') === 'rtl';

        slider.style.transform = isRtl
            ? `translate3d(-${sum}px, 0px, 0px)`
            : `translate3d(${sum}px, 0px, 0px)`;

        const currentItem = items[index - 1];

        if (!currentItem) {
            return;
        }

        currentItem
            .querySelector('.nav-link')
            ?.classList.add('active');

        slider.style.width =
            `${currentItem.offsetWidth}px`;

        callback?.(currentItem);
    }

    resize(instance) {
        const { element } = instance;

        instance.resizeHandler = () => {
            const activeLink = element.querySelector(
                '.nav-item .nav-link.active'
            );

            if (!activeLink) {
                return;
            }

            const item = activeLink.closest('.nav-item');

            if (!item) {
                return;
            }

            const items = Array.from(
                element.querySelectorAll('.nav-item')
            );

            const index = items.indexOf(item) + 1;

            this.updateSlide(
                item,
                items,
                index,
                instance
            );
        };

        window.addEventListener(
            'resize',
            instance.resizeHandler
        );
    }

    updateRTL(instance) {
        const { element } = instance;

        instance.observer = new MutationObserver((mutations) => {
            const directionChanged = mutations.some(
                (mutation) =>
                    mutation.type === 'attributes' &&
                    mutation.attributeName === 'dir'
            );

            if (!directionChanged) {
                return;
            }

            const activeLink = element.querySelector(
                '.nav-item .nav-link.active'
            );

            if (!activeLink) {
                return;
            }

            const item = activeLink.closest('.nav-item');

            if (!item) {
                return;
            }

            const items = Array.from(
                element.querySelectorAll('.nav-item')
            );

            const index = items.indexOf(item) + 1;

            this.updateSlide(
                item,
                items,
                index,
                instance
            );
        });

        instance.observer.observe(
            document.documentElement,
            {
                attributes: true,
                attributeFilter: ['dir'],
            }
        );
    }

    destroy(element) {
        const instance = this.instances.get(element);

        if (!instance) {
            return;
        }

        window.removeEventListener(
            'resize',
            instance.resizeHandler
        );

        instance.observer?.disconnect();

        instance.slider.remove();
        element.classList.remove(instance.elemClass);

        this.instances.delete(element);
    }

    initializeAll(root = document) {
        root
            .querySelectorAll('[data-toggle="slider-tab"]')
            .forEach((element) => {
                this.init(element);
            });
    }
}

const sliderTab = new SliderTab();

// Mantiene compatibilidad con Qompac
window.SliderTab = sliderTab;

export default sliderTab;

// Inicialización inicial
document.addEventListener('DOMContentLoaded', () => {
    sliderTab.initializeAll();
});

// Re-inicialización después de wire:navigate
document.addEventListener('livewire:navigated', () => {
    sliderTab.initializeAll();
});
