import Plugin from 'src/plugin-system/plugin.class'
import { getCsrfToken, createHiddenField } from '../util.js'

export default class EasyCreditRatenkaufCheckout extends Plugin {
    init() {
        let element = document.querySelector('easycredit-checkout');
        if (!element) {
            return;
        }

        document.addEventListener("easycredit-submit", async (e) => {
            if (!e.target.matches('easycredit-checkout')) {
                return;
            }

            var form = document.getElementById('changePaymentForm')

            const token = await getCsrfToken()
            if (token) {
              form.append(createHiddenField('_csrf_token', token))
            }

            form.append(createHiddenField('easycredit[submit]','1'))
            form.append(createHiddenField('easycredit[number-of-installments]', e.detail.numberOfInstallments))
            form.submit()

            return false
        })
    }
}
