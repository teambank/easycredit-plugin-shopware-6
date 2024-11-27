import template from './sw-order-detail.html.twig';
import { isEasyCreditMethod }  from '../../../easycredit/paymentHelper';

const { Component, Context } = Shopware;
const Criteria = Shopware.Data.Criteria;


Component.override('sw-order-detail', {
    template,

    data() {
        return {
            isEasyCreditPayment: false
        };
    },

    computed: {
        // TODO remove with PT-10455
        showTabs() {
            return true;
        }
    },

    watch: {
        orderId: {
            deep: true,
            handler() {
                if (!this.orderId) {
                    this.setIsEasyCreditPayment(null);
                    return;
                }

                const orderRepository = this.repositoryFactory.create('order');
                const orderCriteria = new Criteria(1, 1);
                orderCriteria.addAssociation('transactions');
                orderCriteria.getAssociation('transactions').addSorting(Criteria.sort('createdAt'));

                orderRepository.get(this.orderId, Context.api, orderCriteria).then((order) => {
                    const transactionsQuantity = order.transactions.length;
                    const lastTransactionIndex = transactionsQuantity - 1;
                    if (transactionsQuantity <= 0 ||
                        !order.transactions[lastTransactionIndex].paymentMethodId
                    ) {
                        this.setIsEasyCreditPayment(null);
                        return;
                    }

                    const paymentMethodId = order.transactions[lastTransactionIndex].paymentMethodId;

                    if (paymentMethodId !== undefined && paymentMethodId !== null) {
                        this.setIsEasyCreditPayment(paymentMethodId);
                        this.order = order;
                    }
                });
            },
            immediate: true
        }
    },

    methods: {
        setIsEasyCreditPayment(paymentMethodId) {
            if (!paymentMethodId) {
                return;
            }
            const paymentMethodRepository = this.repositoryFactory.create('payment_method');
            paymentMethodRepository.get(paymentMethodId, Context.api).then(
                (paymentMethod) => {
                    this.isEasyCreditPayment = isEasyCreditMethod(paymentMethod)
                }
            );
        }
    }
});
