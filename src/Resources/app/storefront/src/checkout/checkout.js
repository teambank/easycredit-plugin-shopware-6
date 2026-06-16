import Plugin from 'src/plugin-system/plugin.class'
import ButtonLoadingIndicator from 'src/utility/loading-indicator/button-loading-indicator.util'
import { INDICATOR_POSITION } from 'src/utility/loading-indicator/loading-indicator.util'
import { getCsrfToken, createHiddenField } from '../util.js'

export default class EasyCreditRatenkaufCheckout extends Plugin {
    init() {
        if (!document.querySelector('easycredit-checkout')) {
            return;
        }

        this._submitButtonLoaders = []

        document.addEventListener('easycredit-submit', (e) => this.onEasyCreditSubmit(e))

        const confirmForm = document.getElementById('confirmOrderForm')
        if (confirmForm) {
            confirmForm.addEventListener('submit', (e) => this.onConfirmOrderSubmit(e))
        }
    }

    async onEasyCreditSubmit(e) {
        if (!e.target.matches('easycredit-checkout')) {
            return;
        }

        await this.startEasyCreditCheckout(e.detail?.numberOfInstallments)

        return false
    }

    async onConfirmOrderSubmit(e) {
        const activeCheckout = this.getActiveEasyCreditCheckout()
        if (!activeCheckout || this.isEasyCreditApproved(activeCheckout)) {
            return;
        }

        const form = e.currentTarget

        if (this._hasInvalidFields(form)) {
            return;
        }

        e.preventDefault()

        this.showConfirmSubmitLoading(form, e.submitter)

        await this.startEasyCreditCheckout(this.getNumberOfInstallments(activeCheckout))
    }

    getActiveEasyCreditCheckout() {
        return document.querySelector('easycredit-checkout[is-active="true"]')
    }

    isEasyCreditApproved(component) {
        const paymentPlan = component.getAttribute('payment-plan')

        return paymentPlan !== null && paymentPlan !== ''
    }

    getNumberOfInstallments(component) {
        if (component.numberOfInstallments != null && component.numberOfInstallments !== '') {
            return String(component.numberOfInstallments)
        }

        if (component.financingTerm != null && component.financingTerm !== '') {
            return String(component.financingTerm)
        }

        if (component.shadowRoot) {
            const select = component.shadowRoot.querySelector('select')
            if (select?.value) {
                return select.value
            }
        }

        return ''
    }

    showConfirmSubmitLoading(form, submitter) {
        const formHandler = window.PluginManager.getPluginInstanceFromElement(form, 'FormHandler')

        if (formHandler && typeof formHandler.addLoadingIndicator === 'function') {
            if (!formHandler.submittButtonLoaders?.length) {
                formHandler.addLoadingIndicator()
            }

            return
        }

        this.getConfirmSubmitButtons(form, submitter).forEach((button) => {
            if (button.classList.contains('is-loading-indicator-inner')) {
                return
            }

            const loader = new ButtonLoadingIndicator(button, INDICATOR_POSITION.INNER)
            loader.create()
            this._submitButtonLoaders.push(loader)
        })
    }

    getConfirmSubmitButtons(form, submitter) {
        if (submitter?.type === 'submit') {
            return [submitter]
        }

        const buttons = Array.from(form.querySelectorAll('button[type="submit"]'))

        if (form.id) {
            buttons.push(
                ...Array.from(document.querySelectorAll(`button[type="submit"][form="${form.id}"]`))
            )
        }

        return [...new Set(buttons)]
    }

    _hasInvalidFields(form) {
        if (typeof window.formValidation === 'undefined') {
            return false
        }

        const formFields = form.elements ? [...form.elements] : []

        return window.formValidation.validateForm(form, formFields).length > 0
    }

    async startEasyCreditCheckout(numberOfInstallments) {
        const form = document.getElementById('changePaymentForm')
        if (!form) {
            return;
        }

        const token = await getCsrfToken()
        if (token) {
            form.append(createHiddenField('_csrf_token', token))
        }

        form.append(createHiddenField('easycredit[submit]', '1'))
        form.append(createHiddenField('easycredit[number-of-installments]', numberOfInstallments ?? ''))
        form.submit()
    }
}
