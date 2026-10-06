class IQUtils {
    saveSessionStorage(key, value) {
        window.sessionStorage.setItem(key, value);
    }

    getSessionStorage(key) {
        return window.sessionStorage.getItem(key);
    }

    removeSessionStorage(key) {
        window.sessionStorage.removeItem(key);
    }

    saveLocalStorage(key, value) {
        window.localStorage.setItem(key, value);
    }

    getLocalStorage(key) {
        return window.localStorage.getItem(key);
    }

    removeLocalStorage(key) {
        window.localStorage.removeItem(key);
    }

    getCookie(name) {
        const cookies = document.cookie.split(';');

        for (const cookie of cookies) {
            const value = cookie.trim();

            if (value.startsWith(`${name}=`)) {
                return value.substring(name.length + 1);
            }
        }

        return '';
    }

    setCookie(name, value, exdays) {
        const date = new Date();

        date.setTime(
            date.getTime() + (
                exdays * 24 * 60 * 60 * 1000
            )
        );

        document.cookie =
            `${name}=${value}; expires=${date.toUTCString()}`;
    }

    removeCookie(name) {
        document.cookie =
            `${name}=; expires=Thu, 01 Jan 1970 00:00:01 GMT;`;
    }

    checkStorageKey(key) {
        return window.localStorage.getItem(key) !== null;
    }

    checkSessionStorageKey(key) {
        return window.sessionStorage.getItem(key) !== null;
    }

    checkCookieKey(key) {
        return this.getCookie(key) !== '';
    }

    checkValue(value) {
        return value !== null &&
            value !== undefined &&
            value !== '';
    }

    checkAllStorageKey(key) {
        return this.checkStorageKey(key) ||
            this.checkSessionStorageKey(key) ||
            this.checkCookieKey(key);
    }

    checkStorageArray(key, storages) {
        let result = false;
        const obj = {};

        for (const storage of storages) {
            switch (storage) {
                case 'localStorage':
                    if (
                        this.checkStorageKey(key) &&
                        window.localStorage.getItem(key) !== 'none'
                    ) {
                        result = true;
                        obj.storage = 'localStorage';
                    }
                    break;

                case 'sessionStorage':
                    if (
                        this.checkSessionStorageKey(key) &&
                        window.sessionStorage.getItem(key) !== 'none'
                    ) {
                        result = true;
                        obj.storage = 'sessionStorage';
                    }
                    break;

                case 'cookie':
                    if (this.checkCookieKey(key)) {
                        result = true;
                        obj.storage = 'cookieStorage';
                        obj.cookie = false;
                    }
                    break;

                case 'none':
                    if (
                        window.sessionStorage.getItem(key) === 'none' &&
                        window.localStorage.getItem(key) === 'none'
                    ) {
                        result = true;
                        obj.storage = 'none';
                    }
                    break;
            }
        }

        obj.result = result;

        return obj;
    }

    getUrlParameter(name) {
        return this.getQueryString(name);
    }

    getQueryString(name) {
        const escapedName = name.replace(
            /[[\]]/g,
            '\\$&'
        );

        const regex = new RegExp(
            `[\\?&]${escapedName}=([^&#]*)`
        );

        const results = regex.exec(window.location.search);

        return results === null
            ? ''
            : decodeURIComponent(
                results[1].replace(/\+/g, ' ')
            );
    }

    getElem(selector, elem = document) {
        return elem.querySelector(selector);
    }

    getElems(selector, elem = document) {
        return elem.querySelectorAll(selector);
    }

    setContent(selector, content) {
        let elements = selector;

        if (typeof elements === 'string') {
            elements = this.getElems(elements);
        }

        if (elements?.length !== undefined) {
            elements.forEach((element) => {
                const leftJoin =
                    element.getAttribute('data-leftJoin') ?? '';

                const rightJoin =
                    element.getAttribute('data-rightJoin') ?? '';

                element.innerHTML =
                    leftJoin + content + rightJoin;
            });
        }
    }

    addClass(elem, ...classNames) {
        let elements = elem;

        if (typeof elements === 'string') {
            elements = this.getElems(elements);
        }

        if (elements?.length !== undefined) {
            elements.forEach((element) => {
                element.classList.add(...classNames);
            });

            return;
        }

        elements?.classList.add(...classNames);
    }

    removeClass(elem, ...classNames) {
        let elements = elem;

        if (typeof elements === 'string') {
            elements = this.getElems(elements);
        }

        if (elements?.length !== undefined) {
            elements.forEach((element) => {
                element.classList.remove(...classNames);
            });

            return;
        }

        elements?.classList.remove(...classNames);
    }

    toggleClass(elem, className) {
        elem.classList.toggle(className);
    }

    hasClass(elem, className) {
        return elem.classList.contains(className);
    }

    getAttr(elem, attr) {
        return elem.getAttribute(attr);
    }

    setAttr(elems, object) {
        let elements = elems;

        if (typeof elements === 'string') {
            elements = this.getElems(elements);
        }

        elements?.forEach((element) => {
            element.setAttribute(
                object.prop,
                object.value
            );
        });
    }

    removeAttr(elem, attr) {
        elem.removeAttribute(attr);
    }

    setStyle(elems, object) {
        let elements = elems;

        if (typeof elements === 'string') {
            elements = this.getElems(elements);
        }

        elements?.forEach((element) => {
            Object.entries(object).forEach(
                ([key, value]) => {
                    element.style[key] = value;
                }
            );
        });
    }

    getPosition(elem) {
        let xPosition = 0;
        let yPosition = 0;

        while (elem) {
            xPosition += (
                elem.offsetLeft -
                elem.scrollLeft +
                elem.clientLeft
            );

            yPosition += (
                elem.offsetTop -
                elem.scrollTop +
                elem.clientTop
            );

            elem = elem.offsetParent;
        }

        return {
            x: xPosition,
            y: yPosition,
        };
    }

    getWidth(elem) {
        return elem.offsetWidth;
    }

    getHeight(elem) {
        return elem.offsetHeight;
    }

    createEvent(eventName, eventData = undefined) {
        return new Event(eventName, eventData);
    }

    mergeDeep(target, ...sources) {
        if (!sources.length) {
            return target;
        }

        const source = sources.shift();

        if (
            this.isObject(target) &&
            this.isObject(source)
        ) {
            Object.keys(source).forEach((key) => {
                if (this.isObject(source[key])) {
                    if (!target[key]) {
                        target[key] = {};
                    }

                    this.mergeDeep(
                        target[key],
                        source[key]
                    );
                } else {
                    target[key] = source[key];
                }
            });
        }

        return this.mergeDeep(target, ...sources);
    }

    isObject(value) {
        return value !== null &&
            typeof value === 'object' &&
            !Array.isArray(value);
    }

    getRootVars(property, elem = document.body) {
        let element = elem;

        if (typeof element === 'string') {
            element = this.getElem(element);
        }

        if (!element) {
            return null;
        }

        return (
            getComputedStyle(element)
                .getPropertyValue(property)
                .trim() || null
        );
    }

    setRootVariables(variables) {
        const root = document.documentElement;

        Object.entries(variables).forEach(
            ([key, value]) => {
                root.style.setProperty(key, value);
            }
        );
    }

    removeRootVariables(variables) {
        const root = document.documentElement;

        Object.keys(variables).forEach((key) => {
            root.style.removeProperty(key);
        });
    }

    colorMix(color1, color2, weight = 50) {
        const hexToDecimal = (hex) =>
            parseInt(hex, 16);

        const decimalToHex = (decimal) =>
            decimal.toString(16);

        let color = '#';

        for (let i = 0; i <= 5; i += 2) {
            const value1 = hexToDecimal(
                color1.substring(i, i + 2)
            );

            const value2 = hexToDecimal(
                color2.substring(i, i + 2)
            );

            let value = decimalToHex(
                Math.floor(
                    value2 +
                    (value1 - value2) *
                    (weight / 100)
                )
            );

            while (value.length < 2) {
                value = `0${value}`;
            }

            color += value;
        }

        return color;
    }

    tintColor(color, weight = 50) {
        return this.colorMix(
            'FFFFFF',
            color.substring(1),
            weight
        );
    }

    shadeColor(color, weight = 50) {
        return this.colorMix(
            '000000',
            color.substring(1),
            weight
        );
    }

    hexToRgb(hex) {
        const result =
            /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i
                .exec(hex);

        if (!result) {
            return null;
        }

        return [
            parseInt(result[1], 16),
            parseInt(result[2], 16),
            parseInt(result[3], 16),
        ].join(', ');
    }

    getColorShadeTint(color, value) {
        const style = document.createElement('style');

        style.setAttribute(
            'class',
            'custom-color'
        );

        style.innerHTML = `
            [data-bs-theme-color="custom"] {
                ${color}: ${value};
                ${color}-rgb: ${this.hexToRgb(value)};
                ${color}-bg-subtle: ${this.tintColor(value, 80)};
                ${color}-border-subtle: var(${color}-bg-subtle);
                --bs-link-color-rgb: var(${color}-rgb);
                ${color}-hover-bg: ${this.shadeColor(value, 20)};
                ${color}-hover-border: ${this.shadeColor(value, 30)};
                ${color}-active-bg: ${this.shadeColor(value, 20)};
                ${color}-active-border: ${this.shadeColor(value, 30)};
            }

            [data-bs-theme-color="custom"][data-bs-theme="dark"] {
                ${color}-text-emphasis: ${this.tintColor(value, 40)};
                ${color}-bg-subtle: ${this.shadeColor(value, 80)};
            }
        `;

        document.head.appendChild(style);

        return {};
    }

    debounce(func, wait, immediate = false) {
        let timeout;

        return (...args) => {
            const callNow = immediate && !timeout;

            clearTimeout(timeout);

            timeout = setTimeout(() => {
                timeout = null;

                if (!immediate) {
                    func.apply(this, args);
                }
            }, wait);

            if (callNow) {
                func.apply(this, args);
            }
        };
    }

    getVariableColor() {
        let prefix =
            getComputedStyle(document.body)
                .getPropertyValue('--prefix')
                .trim() || 'bs-';

        const getColor = (name) =>
            getComputedStyle(document.body)
                .getPropertyValue(`--${prefix}${name}`)
                .trim();

        return {
            primary: getColor('primary'),
            info: getColor('info'),
            warning: getColor('warning'),
            primary_light: getColor('success'),
            secondary: getColor('secondary'),
            secondary_light: getColor('danger'),
        };
    }
}

const iqUtils = new IQUtils();

// Compatibilidad con Qompac legacy
window.IQUtils = iqUtils;

export default iqUtils;
