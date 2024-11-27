import template from './sw-order-general-info.html.twig';
import { isEasyCreditMethod } from '../../../easycredit/paymentHelper';

const { Component } = Shopware;

Component.override('sw-order-general-info', {
    template,

    computed: {
        isEasyCreditPayment () {
            let tx = this.order.transactions;
            return tx.length == 1 && isEasyCreditMethod(tx[0].paymentMethod)
        }
    }
});
