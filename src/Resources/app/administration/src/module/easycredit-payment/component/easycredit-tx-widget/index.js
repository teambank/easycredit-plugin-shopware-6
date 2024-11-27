import template from './easycredit-tx-widget.html.twig';
import './easycredit-tx-widget.scss';
import { isEasyCreditMethod }  from '../../../easycredit/paymentHelper';

const { Component, Context } = Shopware;

Component.register('easycredit-tx-widget', {
    template,

    props: {
        order: Object,
        componentType: {
            type: String,
            default: 'status'
        }
    },

    created () {
      this.initRequestConfig()
    },

    computed: {
        isEasyCreditPayment () {
            let tx = this.order.transactions;
            return tx.length == 1 && isEasyCreditMethod(tx[0].paymentMethod)
        },
        transactionId() {
            return this.order.transactions[0].customFields.easycredit_transaction_id
        },
        transactionDate() {
            return this.order.transactions[0].createdAt
        }
    },

    methods: {
        initRequestConfig() {
            window.ratenkaufbyeasycreditOrderManagementConfig = {
                'endpoints': {
                    'get': 'api/v2/easycredit/transaction/{transactionId}',
                    'capture': 'api/v2/easycredit/transaction/{transactionId}/capture',
                    'refund': 'api/v2/easycredit/transaction/{transactionId}/refund'
                },
                'request_config': {
                    'headers': {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + Context.api.authToken.access
                    }
                }
            }            
        }
    }
});