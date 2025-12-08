import Plugin from 'src/plugin-system/plugin.class';

export default class EasyCreditRatenkaufWidget extends Plugin {
    init() {
        this.initWidget(document.querySelector('.product-detail-buy'));
        this.initWidget(document.querySelector('.cms-element-product-listing'));
        this.initWidget(document.querySelector('.is-act-cartpage'));

        this.registerOffCanvas();
        this.registerListingListener();
    }

    registerOffCanvas() {
        const element = document.querySelector('[data-off-canvas-cart],[data-offcanvas-cart]');
        if (!element) {
            return;
        }
        const instance = window.PluginManager.getPluginInstanceFromElement(element, 'OffCanvasCart');

        if (!instance) {
            return;
        }

        instance.$emitter.subscribe('offCanvasOpened', () => {
            this.initWidget(document.querySelector('div.offcanvas-cart'));
        });
    }

    registerListingListener() {
        const element = document.querySelector('[data-listing-pagination]');
        if (!element) {
            return;
        }
        const instance = window.PluginManager.getPluginInstanceFromElement(element, 'Listing');
        if (!instance) {
            return;
        }

        instance.$emitter.subscribe('Listing/afterRenderResponse', () => {
            this.initWidget(document.querySelector('.cms-element-product-listing'));
            this.initWidget(document.querySelector('.product-detail-buy'));
        });
    }

    initWidget(container) {
        if (!container) {
            return;
        }

        const selector = this.getMeta('widget-selector', container);
        if (selector === null) {
            return;
        }
        const apiKey = this.getMeta('api-key');
        if (apiKey === null) {
            return;
        }

        const processedSelector = this.processSelector(selector);
        const elements = container.querySelectorAll(processedSelector.selector);
        for (const element of elements) {
            this.applyWidget(element, processedSelector.attributes);

            if (processedSelector.selector === '.checkout-aside-action:not(.d-grid)') {
                break; // fix specifically for cart selector in SW 6.4.20.2 or lower, .d-grid is missing
            }
        }
    }

    applyWidget(element, attributes) {
        let amount = this.getMeta('amount', element);

        if (null === amount || isNaN(amount)) {
            const priceContainer = element.parentNode;
            amount =
                priceContainer && priceContainer.querySelector('[itemprop=price]')
                    ? priceContainer.querySelector('[itemprop=price]').content
                    : null;
        }

        if (null === amount || isNaN(amount)) {
            return;
        }

        let widget = document.createElement('easycredit-widget');
        widget.setAttribute('webshop-id', this.getMeta('api-key'));
        widget.setAttribute('amount', amount);
        widget.setAttribute('payment-types', this.getMeta('payment-types', element));

        if (this.getMeta('disable-flexprice', element)) {
            widget.setAttribute('disable-flexprice', 'true');
        } else {
            widget.removeAttribute('disable-flexprice');
        }

        if (attributes) {
            for (const [name, value] of Object.entries(attributes)) {
                widget.setAttribute(name, value);
            }
        }
        element.appendChild(widget);
    }

    getMeta(key, element = null) {
        // 1) Try nearest element-scoped JSON config
        if (element) {
            const localConfig = this.searchUpTheTree(element, 'script.easycredit-config[type="application/json"]');
            const extracted = this.extractConfigValueFromJson(localConfig && localConfig.textContent, key);
            if (extracted !== null && extracted !== undefined) {
                return extracted;
            }
        }

        // 2) Try global JSON config in head
        const configEl = document.head.querySelector('.easycredit-config');
        const extractedHead = this.extractConfigValueFromJson(configEl && configEl.textContent, key);
        if (extractedHead !== null && extractedHead !== undefined) {
            return extractedHead;
        }

        // 3) Fallback to meta tags for legacy data
        const selector = 'meta[name=easycredit-' + key + ']';
        let meta;
        if (element) {
            meta = this.searchUpTheTree(element, selector);
        } else {
            meta = document.querySelector(selector);
        }
        if (meta) {
            return meta.content;
        }
        return null;
    }

    processSelector(selector) {
        const regExp = /(.+) easycredit-widget(\[.+?\])$/;

        let match;
        if ((match = selector.match(regExp))) {
            const attributes = match[2]
                .split(']')
                .map((item) => item.slice(1).split('='))
                .filter(([k, v]) => k)
                .reduce((acc, [k, v]) => ({ ...acc, [k]: v }), {});

            return {
                selector: match[1],
                attributes: attributes,
            };
        }
        return {
            selector: selector,
        };
    }

    searchUpTheTree(element, selector) {
        while (element && element.parentElement) {
            const parent = element.parentElement;
            const match = Array.from(parent.children).find(
                (sibling) => sibling !== element && sibling.matches(selector)
            );

            if (match) {
                return match;
            }

            element = parent;
        }

        // If we reached <html>, check <head> as a last resort
        if (element === document.documentElement) {
            return Array.from(document.head.children).find((el) => el.matches(selector)) || null;
        }
        return null;
    }

    extractConfigValue(data, key) {
        if (!data || typeof data !== 'object') {
            return null;
        }
        if (!Object.prototype.hasOwnProperty.call(data, key)) {
            return null;
        }
        const value = data[key];
        if (Array.isArray(value)) {
            return value.join(',');
        }
        if (typeof value === 'object') {
            return Object.values(value).join(',');
        }
        if (typeof value === 'boolean') {
            return value ? 'true' : '';
        }
        return value;
    }

    extractConfigValueFromJson(jsonText, key) {
        if (!jsonText || (typeof jsonText === 'string' && jsonText.trim() === '')) {
            return null;
        }
        try {
            const data = JSON.parse(jsonText);
            return this.extractConfigValue(data, key);
        } catch (e) {
            // ignore malformed JSON
            return null;
        }
    }
}
