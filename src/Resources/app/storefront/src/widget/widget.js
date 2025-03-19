import Plugin from 'src/plugin-system/plugin.class'

export default class EasyCreditRatenkaufWidget extends Plugin {
    init() {
        this.initWidget(
            document.querySelector('.product-detail-buy')
        )
        this.initWidget(
            document.querySelector('.cms-element-product-listing')
        )
        this.registerOffCanvas();
    }

    registerOffCanvas () {
        let element = document.querySelector('[data-off-canvas-cart]')
        if (!element) {
           return
        }
        window.PluginManager
            .getPluginInstanceFromElement(element, 'OffCanvasCart')
            .$emitter
            .subscribe('offCanvasOpened', this.onOffCanvasOpened.bind(this));
    }

    onOffCanvasOpened () {
        this.initWidget(
            document.querySelector('div.cart-offcanvas')
        )
    }

    initWidget(container) {
        if (!container) {
            return
        }

        const selector = this.getMeta('widget-selector', container)
        if (selector === null) {
            return
        }
        const apiKey = this.getMeta('api-key')
        if (apiKey === null) {
            return
        }

        const processedSelector = this.processSelector(selector)
        container.querySelectorAll(processedSelector.selector).forEach((element) => {
            this.applyWidget(element, processedSelector.attributes)
        })
    }

    applyWidget(element, attributes) {
        let amount = this.getMeta('amount', element)

        if (null === amount || isNaN(amount)) {
            const priceContainer = element.parentNode
            amount = priceContainer && priceContainer.querySelector('[itemprop=price]') ? 
                priceContainer.querySelector('[itemprop=price]').content 
                : null
        }
        
        if (null === amount || isNaN(amount)) {
            return
        }

        let widget = document.createElement('easycredit-widget')
        widget.setAttribute('webshop-id', this.getMeta('api-key'))
        widget.setAttribute('amount', amount)
        widget.setAttribute('payment-types', this.getMeta('payment-types', element))
        
        if (this.getMeta('disable-flexprice')) {
            widget.setAttribute('disable-flexprice','true')
        } else {
            widget.removeAttribute('disable-flexprice')
        }

        if (attributes) {
            for (const [name, value] of Object.entries(attributes)) {
                widget.setAttribute(name, value);
            }
        }
        element.appendChild(widget)
    }

    getMeta(key, element = null) {
        const selector = 'meta[name=easycredit-' + key + ']'

        let meta;
        if (element) {
            meta = this.searchUpTheTree(element, selector)
        } else {
            meta = document.querySelector(selector);
        }
        if (meta) {
            return meta.content
        }
        return null
    }

    processSelector (selector) {
        const regExp = /(.+) easycredit-widget(\[.+?\])$/

        let match
        if (match = selector.match(regExp)) {

            const attributes = match[2].split(']')
                .map(item => item.slice(1).split('='))
                .filter(([k, v]) => k)
                .reduce((acc, [k, v]) => ({ ...acc, [k]: v }), {})

                return {
                selector: match[1],
                attributes: attributes
            }
        }
        return {
            selector: selector
        }
    }

    searchUpTheTree(element, selector) {
        while (element && element.parentElement) {
            const parent = element.parentElement;
            const match = Array.from(parent.children)
                .find(sibling => sibling !== element && sibling.matches(selector));

            if (match) {
                return match;
            }

            element = parent;
        }

        // If we reached <html>, check <head> as a last resort
        if (element === document.documentElement) {
            return Array.from(document.head.children).find(el => el.matches(selector)) || null;
        }
        return null;
    }
}
