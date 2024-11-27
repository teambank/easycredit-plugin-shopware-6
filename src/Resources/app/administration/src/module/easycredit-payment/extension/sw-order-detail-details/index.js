import template from './sw-order-detail-details.html.twig';
import { isEasyCreditMethod } from '../../../easycredit/paymentHelper';

const { Component } = Shopware;

Component.override('sw-order-detail-details', {
    template,

    computed: {
        isEasyCreditPayment () {
            let tx = this.order.transactions;
            return tx.length == 1 && isEasyCreditMethod(tx[0].paymentMethod)
        }
    }
});
