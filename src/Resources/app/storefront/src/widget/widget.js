import Plugin from 'src/plugin-system/plugin.class'

export default class EasyCreditRatenkaufWidget extends Plugin {
    init() {
        this.initWidget(document)
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

        const selector = this.getMeta('widget-selector', container)
        if (selector === null) {
            return
        }
        if (this.getMeta('api-key') === null) {
            return
        }

        let processedSelector = this.processSelector(selector)

        let elements = container.querySelectorAll(processedSelector.selector)
        elements.forEach((element) => {
            this.applyWidget(container, element, processedSelector.attributes)
        })
    }

    applyWidget(container, element, attributes) {
        let amount = this.getMeta('amount', container, element)

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
        widget.setAttribute('payment-types', this.getMeta('payment-types'))
        
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

    getMeta(key, container = null, element = null) {
        let meta

        if (container === null) {
            container = document
        }

        const selector = 'meta[name=easycredit-' + key + ']'

        if (element) {
            let box
            if (box = element.closest('.cms-listing-col')) {
                if (meta = box.querySelector(selector)) {
                    return meta.content
                }
            }
        }
        if (meta = container.querySelector(selector)) {
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
}
