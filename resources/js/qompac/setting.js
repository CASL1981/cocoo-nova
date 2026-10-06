const selectors = {
    radio: () => document.querySelectorAll('[data-setting="radio"]'),
    checkbox: () => document.querySelectorAll('[data-setting="checkbox"]'),
    attribute: () => document.querySelectorAll('[data-setting="attribute"]'),
    style: () => document.querySelectorAll('[data-setting="style"]'),
    input: () => document.querySelectorAll('[data-setting="input"]'),
    select: () => document.querySelectorAll('[data-setting="select"]'),
    color: () => document.querySelectorAll('[data-setting="color"]'),
};

const defaultSettingOption = () => ({
    app_name: {
        target: '[data-setting="app_name"]',
        type: 'text',
        value: 'Qompac Ui',
    },

    theme_scheme_direction: {
        target: 'html',
        choices: ['ltr', 'rtl'],
        value: 'ltr',
    },

    theme_scheme: {
        target: 'html',
        choices: ['light', 'dark', 'auto'],
        value: 'light',
    },

    theme_color: {
        target: 'html',
        choices: [
            'default',
            'color-1',
            'color-2',
            'color-3',
            'color-4',
            'color-5',
        ],
        type: 'variable',
        colors: {},
        value: 'default',
    },

    theme_font_size: {
        target: 'html',
        choices: [
            'theme-fs-sm',
            'theme-fs-md',
            'theme-fs-lg',
        ],
        value: 'theme-fs-sm',
    },

    page_layout: {
        target: '#page_layout',
        choices: ['container', 'container-fluid'],
        value: 'container',
    },

    sidebar_color: {
        target: '[data-toggle="main-sidebar"]',
        choices: [
            'sidebar-white',
            'sidebar-dark',
            'sidebar-color',
        ],
        value: 'sidebar-white',
    },

    sidebar_type: {
        target: '[data-toggle="main-sidebar"]',
        choices: [
            'sidebar-hover',
            'sidebar-mini',
            'sidebar-soft',
        ],
        value: [],
    },

    sidebar_menu_style: {
        target: '[data-toggle="main-sidebar"]',
        choices: [
            'sidebar-default navs-rounded',
            'sidebar-default navs-rounded-all',
            'sidebar-default navs-pill',
            'sidebar-default navs-pill-all',
        ],
        value: 'sidebar-default navs-rounded-all',
    },
});

const defaultSetting = () => ({
    saveLocal: '',
    storeKey: '',
    setting: defaultSettingOption(),

    beforeInit: (callback) => callback,

    afterInit: (callback) => callback,
});

class IQSetting {
    constructor(options = undefined) {
        this.options = {};
        this.arg = options;

        this.extend(defaults);
        this.getStorageValue(this.options.storeKey);
        this.updateOptionFromStorage();

        if (typeof this.options.beforeInit === 'function') {
            this.options.beforeInit(this);
        }

        this.init();

        if (typeof this.options.afterInit === 'function') {
            this.options.afterInit(this);
        }

        this.addListeners();

        return this;
    }

    extend(defaultOptions) {
        const baseOptions = {
            ...defaultOptions,
            setting: Object.fromEntries(
                Object.entries(defaultOptions.setting).map(
                    ([key, value]) => [
                        key,
                        {
                            ...value,
                            ...(value.colors
                                ? {
                                    colors: {
                                        ...value.colors,
                                    },
                                }
                                : {}),
                            ...(value.choices
                                ? {
                                    choices: [
                                        ...value.choices,
                                    ],
                                }
                                : {}),
                        },
                    ]
                )
            ),
        };

        if (
            this.arg &&
            typeof this.arg === 'object' &&
            !Array.isArray(this.arg)
        ) {
            this.options = IQUtils.mergeDeep(
                baseOptions,
                this.arg
            );
        } else {
            this.options = baseOptions;
        }
    }

    fnCall(
        key,
        value = this.getSettingKey(key)?.value
    ) {
        if (
            typeof key !== 'string' ||
            typeof this[key] !== 'function'
        ) {
            return;
        }

        this[key](value);
    }

    init() {
        Object.keys(this.options.setting).forEach((key) => {
            this.fnCall(key);
        });

        this.saveOption();
    }

    reInit() {
        this.destroy();

        this.extend(defaultSetting());

        this.saveLocal(this.options.saveLocal);

        this.init();

        this.afterUpdate(
            'reinit',
            this.options
        );
    }

    afterUpdate(
        key,
        value,
        currentValue = ''
    ) {
        const event = new CustomEvent(key, {
            detail: {
                value,
                name: key,
                currentValue,
            },
        });

        document.dispatchEvent(event);

        this.saveOption();
    }

    destroy() {
        this.removeListeners();
    }

    refreshListeners() {
        this.removeListeners();
        this.syncControls();
        this.addListeners();
    }

    syncControls() {
        Object.keys(this.options.setting).forEach((key) => {
            const setting = this.getSettingKey(key);

            if (!setting) {
                return;
            }

            if (Array.isArray(setting.value)) {
                this.__checkboxInputChange__(key);
                return;
            }

            this.__radioInputChange__(key);

            if (key === 'app_name') {
                this.__inputChange__(key, setting.value);
            }
        });

        // Sincronizar colores personalizados
        const themeColors =
            this.options.setting.theme_color?.colors || {};

        document
            .querySelectorAll('[data-setting="color"]')
            .forEach((input) => {
                const extra = input.dataset.extra;

                if (!extra) {
                    return;
                }

                const key = `--{{prefix}}${extra}`;
                const value = themeColors[key];

                if (value) {
                    input.value = value;
                }
            });
    }

    addListeners() {
        this.addRadioListener();
        this.addCheckboxListener();
        this.addAttributeListener();
        this.addStyleListener();
        this.addInputListener();
        this.addColorListener();
    }

    removeListeners() {
        if (this._radioHandler) {
            selectors.radio().forEach((element) => {
                element.removeEventListener(
                    'change',
                    this._radioHandler
                );
            });
        }

        if (this._checkboxHandler) {
            selectors.checkbox().forEach((element) => {
                element.removeEventListener(
                    'change',
                    this._checkboxHandler
                );
            });
        }

        if (this._styleHandler) {
            selectors.style().forEach((element) => {
                element.removeEventListener(
                    'change',
                    this._styleHandler
                );
            });
        }

        if (this._attributeHandler) {
            selectors.attribute().forEach((element) => {
                element.removeEventListener(
                    'change',
                    this._attributeHandler
                );
            });
        }

        if (this._inputHandler) {
            selectors.input().forEach((element) => {
                element.removeEventListener(
                    'input',
                    this._inputHandler
                );
            });
        }

        if (this._colorHandlers) {
            this._colorHandlers.forEach(
                ({ element, handler }) => {
                    element.removeEventListener(
                        'input',
                        handler
                    );
                }
            );
        }

        this._radioHandler = null;
        this._checkboxHandler = null;
        this._styleHandler = null;
        this._attributeHandler = null;
        this._inputHandler = null;
        this._colorHandlers = [];
    }

    setMainOption(key, value) {
        this.options[key] = value;
    }

    getSettingOptions() {
        return this.options.settings;
    }

    getSettingKey(key) {
        return this.options.setting[key];
    }

    setSettingOption(key, value, manual = false) {
        if (
            manual &&
            this.options.setting[key]
        ) {
            this.options.setting[key].value = value;
        }
    }

    setSettingColorChoice(key, pair) {
        this.options.setting[key].colors[pair.key] =
            pair.value;
    }

    getSettingJson() {
        const json = {};

        Object.keys(this.options).forEach((key) => {
            if (
                key === 'afterInit' ||
                key === 'beforeInit'
            ) {
                return;
            }

            json[key] = {
                ...this.options[key],
            };

            if (key === 'setting') {
                Object.keys(json[key]).forEach(
                    (subKey) => {
                        delete json[key][subKey].target;
                        delete json[key][subKey].type;
                        delete json[key][subKey].choices;
                    }
                );
            }
        });

        this.options = IQUtils.mergeDeep(
            {
                ...defaults,
                setting: Object.fromEntries(
                    Object.entries(defaults.setting).map(
                        ([key, value]) => [
                            key,
                            {
                                ...value,
                                ...(value.colors
                                    ? {
                                        colors: {
                                            ...value.colors,
                                        },
                                    }
                                    : {}),
                                ...(value.choices
                                    ? {
                                        choices: [
                                            ...value.choices,
                                        ],
                                    }
                                    : {}),
                            },
                        ]
                    )
                ),
            },
            json
        );

        return JSON.stringify(
            json,
            null,
            4
        );
    }

    saveOption() {
        const key = this.options.storeKey;
        const value = this.options;

        if (
            key === undefined ||
            value === undefined
        ) {
            return;
        }

        switch (this.options.saveLocal) {
            case 'localStorage':
                sessionStorage.removeItem(key);

                return IQUtils.saveLocalStorage(
                    key,
                    JSON.stringify(value)
                );

            case 'sessionStorage':
                localStorage.removeItem(key);

                return IQUtils.saveSessionStorage(
                    key,
                    JSON.stringify(value)
                );

            case 'cookieStorage':
                return IQUtils.setCookie(
                    key,
                    JSON.stringify(value)
                );

            default:
                return;
        }
    }

    getOption(key) {
        if (
            localStorage.getItem(key) === 'none' ||
            sessionStorage.getItem(key) === 'none'
        ) {
            return 'none';
        }

        let value =
            localStorage.getItem(key);

        if (value === null) {
            value =
                sessionStorage.getItem(key);
        }

        if (value === null || value === '') {
            return undefined;
        }

        try {
            return JSON.parse(value);
        } catch {
            return value;
        }
    }

    updateOptionFromStorage() {
        const data = this.getOption(
            this.options.storeKey
        );

        if (data === 'none') {
            this.options.saveLocal = 'none';
            return this.options;
        }

        if (data !== undefined) {
            this.options = data;
        }

        return this.options;
    }

    getStorageValue(key) {
        const checkKey =
            IQUtils.checkStorageArray(
                key,
                [
                    'localStorage',
                    'sessionStorage',
                    'cookieStorage',
                    'none',
                ]
            );

        let defaultStorage =
            this.options.saveLocal;

        if (checkKey.result) {
            defaultStorage =
                checkKey.storage;
        }

        IQUtils.getElems(
            'input[name="saveLocal"]'
        ).forEach((element) => {
            element.checked =
                element.value === defaultStorage;
        });
    }

    __radioInputChange__(key) {
        const object =
            this.getSettingKey(key);

        IQUtils.getElems(
            `input[name="${key}"]`
        ).forEach((element) => {
            element.checked =
                element.value === object.value;
        });
    }

    __checkboxInputChange__(key) {
        const object =
            this.getSettingKey(key);

        IQUtils.getElems(
            `input[name="${key}"]`
        ).forEach((element) => {
            element.checked =
                object.value.includes(
                    element.value
                );
        });
    }

    __inputChange__(key, value) {
        IQUtils.getElems(
            `input[name="${key}"]`
        ).forEach((element) => {
            element.value = value;
        });
    }

    __selectInputChange__(key) {
        const object =
            this.getSettingKey(key);

        IQUtils.getElems(
            `select[name="${key}"]`
        ).forEach((element) => {
            element.value = object.value;
        });
    }

    __radioUpdate__(key, value, callback) {
        const object =
            this.getSettingKey(key);

        if (value !== null) {
            object.value = value;
            this.setSettingOption(
                key,
                value
            );
        }

        if (object.target !== null) {
            object.choices.forEach((choice) => {
                IQUtils.removeClass(
                    object.target,
                    ...choice.split(' ')
                );
            });

            IQUtils.addClass(
                object.target,
                ...value.split(' ')
            );
        }

        this.__radioInputChange__(key);

        if (typeof callback === 'function') {
            callback(
                key,
                value,
                object
            );
        }

        this.afterUpdate(
            key,
            value
        );
    }

    __styleUpdate__(
        key,
        pair = {
            prop: '',
            value: 'value',
        },
        callback
    ) {
        const object =
            this.getSettingKey(key);

        if (pair.value !== null) {
            object.value = pair.value;

            this.setSettingOption(
                key,
                pair.value
            );
        }

        if (object.target !== null) {
            IQUtils.setStyle(
                object.target,
                pair
            );
        }

        this.__radioInputChange__(key);

        if (typeof callback === 'function') {
            callback(
                key,
                pair.value
            );
        }

        this.afterUpdate(
            key,
            pair
        );
    }

    __attributeUpdate__(
        key,
        pair = {
            prop: 'color',
            value: 'value',
        },
        callback
    ) {
        const object =
            this.getSettingKey(key);

        if (pair.value !== null) {
            object.value = pair.value;

            this.setSettingOption(
                key,
                pair.value
            );
        }

        if (object.target !== null) {
            IQUtils.setAttr(
                object.target,
                pair
            );
        }

        this.__radioInputChange__(key);

        if (typeof callback === 'function') {
            callback(
                key,
                pair.value
            );
        }

        this.afterUpdate(
            key,
            pair
        );
    }

    __checkboxUpdate__(
        key,
        value,
        currentValue,
        callback
    ) {
        const object =
            this.getSettingKey(key);

        if (value !== null) {
            object.value = value;

            this.setSettingOption(
                key,
                value
            );
        }

        if (object.target !== null) {
            object.choices.forEach((choice) => {
                IQUtils.removeClass(
                    object.target,
                    choice
                );
            });

            object.value.forEach(
                (className) => {
                    IQUtils.addClass(
                        object.target,
                        className
                    );
                }
            );
        }

        this.__checkboxInputChange__(key);

        if (typeof callback === 'function') {
            callback(
                key,
                value
            );
        }

        this.afterUpdate(
            key,
            value,
            currentValue
        );
    }

    __inputUpdate__(
        key,
        value,
        callback
    ) {
        const object =
            this.getSettingKey(key);

        if (value !== null) {
            object.value = value;

            this.setSettingOption(
                key,
                value
            );
        }

        if (object.target !== null) {
            IQUtils.setContent(
                object.target,
                value.substring(0, 10)
            );
        }

        this.__inputChange__(
            key,
            value
        );

        if (typeof callback === 'function') {
            callback(
                key,
                value
            );
        }

        this.afterUpdate(
            key,
            value
        );
    }

    __updateThemeColor__(
        key,
        value = null
    ) {
        const object =
            this.getSettingKey(key);

        if (value !== null) {
            object.value = value;

            this.setSettingOption(
                key,
                value
            );
        }

        if (object.target !== null) {
            object.choices.forEach((choice) => {
                IQUtils.removeClass(
                    object.target,
                    choice
                );
            });

            if (object.value !== 'custom') {
                this.resetColor(key);
            }

            if (
                typeof object.value !== 'object'
            ) {
                this.__attributeUpdate__(
                    'theme_color',
                    {
                        prop: 'data-bs-theme-color',
                        value: 'custom',
                    }
                );

                const prefix =
                    IQUtils.getRootVars(
                        '--prefix'
                    ) || 'bs-';

                let newColors = {};

                document
                    .querySelectorAll('.custom-color')
                    .forEach((element) => {
                        element.remove();
                    });

                Object.entries(
                    object.colors
                ).forEach(
                    ([colorKey, colorValue]) => {
                        const normalizedKey =
                            colorKey.replace(
                                '{{prefix}}',
                                prefix
                            );

                        newColors = {
                            ...newColors,
                            ...IQUtils.getColorShadeTint(
                                normalizedKey,
                                colorValue
                            ),
                        };
                    }
                );
            }
        }

        this.__radioInputChange__(key);

        this.afterUpdate(
            key,
            value
        );
    }

    resetColor(key) {
        const choices =
            defaults.setting.theme_color.choices
                .find(
                    (choice) =>
                        choice.name === 'custom'
                );

        if (!choices) {
            return;
        }

        Object.entries(
            defaults.setting.theme_color.colors
        ).forEach(
            ([index, value]) => {
                this.setSettingColorChoice(
                    key,
                    {
                        key: index,
                        value,
                    }
                );
            }
        );
    }

    __updateOption__(key, value) {
        this.setMainOption(
            key,
            value
        );

        this.saveOption();
        this.updateOptionFromStorage();
    }

    addRadioListener() {
        this._radioHandler = (event) => {
            const value =
                event.target.value;

            const key =
                event.target.getAttribute('name');

            if (key === 'theme_color') {
                this.__attributeUpdate__(
                    'theme_color',
                    {
                        prop: 'data-bs-theme-color',
                        value,
                    }
                );
            }

            this[key]?.call(
                this,
                value
            );
        };

        selectors.radio().forEach(
            (element) => {
                element.addEventListener(
                    'change',
                    this._radioHandler
                );
            }
        );
    }

    addCheckboxListener() {
        this._checkboxHandler = (event) => {
            const key =
                event.target.getAttribute('name');

            const values = [];

            document
                .querySelectorAll(
                    `[name="${key}"]`
                )
                .forEach((element) => {
                    if (element.checked) {
                        values.push(
                            element.value
                        );
                    }
                });

            this[key]?.call(
                this,
                values,
                event.target.value
            );
        };

        selectors.checkbox().forEach(
            (element) => {
                element.addEventListener(
                    'change',
                    this._checkboxHandler
                );
            }
        );
    }

    addStyleListener() {
        this._styleHandler = (event) => {
            const value =
                event.target.value;

            const key =
                event.target.getAttribute('name');

            this[key]?.call(
                this,
                value
            );
        };

        selectors.style().forEach(
            (element) => {
                element.addEventListener(
                    'change',
                    this._styleHandler
                );
            }
        );
    }

    addAttributeListener() {
        this._attributeHandler = (event) => {
            const value =
                event.target.value;

            const key =
                event.target.getAttribute('name');

            this[key]?.call(
                this,
                value
            );
        };

        selectors.attribute().forEach(
            (element) => {
                element.addEventListener(
                    'change',
                    this._attributeHandler
                );
            }
        );
    }

    addInputListener() {
        this._inputHandler = (event) => {
            const value =
                event.target.value || '';

            const key =
                event.target.getAttribute('name');

            this[key]?.call(
                this,
                value
            );
        };

        selectors.input().forEach(
            (element) => {
                element.addEventListener(
                    'input',
                    this._inputHandler
                );
            }
        );
    }

    addColorListener() {
        this._colorHandlers = [];

        selectors.color().forEach(
            (element) => {
                const debounceFunction =
                    IQUtils.debounce(
                        (name, value) => {
                            this.setSettingColorChoice(
                                name,
                                value
                            );

                            this.theme_color(
                                'custom'
                            );
                        },
                        200,
                        false
                    );

                const handler = (event) => {
                    const value = {
                        key:
                            `--{{prefix}}${event.target.dataset.extra}`,
                        value:
                            event.target.value,
                    };

                    debounceFunction(
                        event.target.name,
                        value
                    );
                };

                element.addEventListener(
                    'input',
                    handler
                );

                this._colorHandlers.push({
                    element,
                    handler,
                });
            }
        );
    }

    app_name(value) {
        if (value !== null) {
            this.__inputUpdate__(
                'app_name',
                value
            );
        }
    }

    theme_scheme(value) {
        if (value !== null) {
            this.__attributeUpdate__(
                'theme_scheme',
                {
                    prop: 'data-bs-theme',
                    value,
                },
                () => {
                    const html =
                        document.documentElement;

                    if (value === 'auto') {
                        html.setAttribute(
                            'data-bs-theme',
                            matchMedia(
                                '(prefers-color-scheme: light)'
                            ).matches
                                ? 'light'
                                : 'dark'
                        );

                        return;
                    }

                    html.setAttribute(
                        'data-bs-theme',
                        value
                    );
                }
            );
        }
    }

    theme_scheme_direction(value) {
        if (value !== null) {
            this.__attributeUpdate__(
                'theme_scheme_direction',
                {
                    prop: 'dir',
                    value,
                },
                () => {
                    this.rtlChange(
                        value === 'rtl'
                    );

                    const fontSelector =
                        document.querySelector(
                            '[data-select="font"]'
                        );

                    if (
                        fontSelector &&
                        typeof window.$ !== 'undefined' &&
                        window.$(fontSelector)
                            .data('select2')
                    ) {
                        window.$(fontSelector)
                            .select2('destroy')
                            .select2();
                    }
                }
            );
        }
    }

    theme_color(value) {
        if (value !== null) {
            if (value === 'custom') {
                this.__updateThemeColor__(
                    'theme_color'
                );
            } else {
                this.__attributeUpdate__(
                    'theme_color',
                    {
                        prop: 'data-bs-theme-color',
                        value,
                    }
                );
            }
        }
    }

    theme_font_size(value) {
        if (value !== null) {
            this.__radioUpdate__(
                'theme_font_size',
                value
            );
        }
    }

    sidebar_color(value) {
        if (value !== null) {
            this.__radioUpdate__(
                'sidebar_color',
                value
            );
        }
    }

    page_layout(value) {
        if (value !== null) {
            this.__radioUpdate__(
                'page_layout',
                value
            );
        }
    }

    sidebar_type(
        value,
        currentValue
    ) {
        if (value !== null) {
            this.__checkboxUpdate__(
                'sidebar_type',
                value,
                currentValue
            );
        }
    }

    sidebar_menu_style(value) {
        if (value !== null) {
            this.__radioUpdate__(
                'sidebar_menu_style',
                value
            );
        }
    }

    rtlChange(check) {
        IQUtils.addClass(
            '.offcanvas-start',
            'on-rtl',
            'start'
        );

        IQUtils.addClass(
            '.offcanvas-end',
            'on-rtl',
            'end'
        );

        if (check) {
            IQUtils.addClass(
                '.on-rtl.start',
                'offcanvas-end'
            );

            IQUtils.removeClass(
                '.on-rtl.start',
                'offcanvas-start'
            );

            IQUtils.addClass(
                '.on-rtl.end',
                'offcanvas-start'
            );

            IQUtils.removeClass(
                '.on-rtl.end',
                'offcanvas-end'
            );
        } else {
            IQUtils.addClass(
                '.on-rtl.start',
                'offcanvas-start'
            );

            IQUtils.removeClass(
                '.on-rtl.start',
                'offcanvas-end'
            );

            IQUtils.addClass(
                '.on-rtl.end',
                'offcanvas-end'
            );

            IQUtils.removeClass(
                '.on-rtl.end',
                'offcanvas-start'
            );
        }
    }

    refreshListeners() {
        this.removeListeners();
        this.addListeners();
    }
}

const defaults = defaultSetting();

window.IQSetting = IQSetting;

export default IQSetting;
