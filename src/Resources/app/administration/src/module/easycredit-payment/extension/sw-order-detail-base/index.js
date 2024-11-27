import template from './sw-order-detail-base.html.twig';
import { isEasyCreditMethod } from '../../../easycredit/paymentHelper';

const { Component } = Shopware;

Component.override('sw-order-detail-base', {
    template,

    computed: {
        isEasyCreditPayment () {
            let tx = this.order.transactions;
            return tx.length == 1 && isEasyCreditMethod(tx[0].paymentMethod)
        }
    }
});
