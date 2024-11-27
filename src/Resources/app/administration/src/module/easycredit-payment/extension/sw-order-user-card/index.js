import template from './sw-order-user-card.html.twig';
import './sw-order-user-card.scss';
import { isEasyCreditMethod } from '../../../easycredit/paymentHelper';

const { Component } = Shopware;

Component.override('sw-order-user-card', {
    template,

    computed: {
        isEasyCreditPayment () {
            let tx = this.currentOrder.transactions;
            return tx.length == 1 && isEasyCreditMethod(tx[0].paymentMethod)
        }
    }
});
