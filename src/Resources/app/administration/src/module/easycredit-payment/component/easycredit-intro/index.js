import template from './easycredit-intro.html.twig';
import './easycredit-intro.scss';

const { Component } = Shopware;

Component.register('easycredit-intro', {
    template,

    inject: ['EasyCreditRatenkaufApiCredentialsService','repositoryFactory'],
    data() {
        return {
            billPaymentActive: false,
            paymentMethodStatuses: {}
        };
    },

    computed: {
        assetFilter() {
            return Shopware.Filter.getByName('asset')
        }
    },

    beforeMount () {
        this.fetchWebshopInfo().then((data) => {
            this.billPaymentActive = data.billPaymentActive;
        });

        this.checkPaymentMethodActivation('easycredit_ratenkauf');
        this.checkPaymentMethodActivation('easycredit_rechnung');
    },
    methods: {
        getConfigComponent () {
            var component = this
            while (component.$parent) {
                if (typeof component.currentSalesChannelId !== 'undefined') {
                    return component
                }
                component = component.$parent
            }
        },
        getConfig(salesChannelId) {
            return this.getConfigComponent().actualConfigData[salesChannelId]
        },
        getCurrentSalesChannelId() {
            return this.getConfigComponent().currentSalesChannelId
        },
        fetchWebshopInfo () {
            const salesChannelId = this.getCurrentSalesChannelId();
            const webshopId = this.getConfig(salesChannelId)['EasyCreditRatenkauf.config.webshopId'] ||
                this.getConfig(null)['EasyCreditRatenkauf.settings.webshopId'];
            const url = `https://ratenkauf.easycredit.de/api/payment/v3/webshop/${webshopId}`;

            return fetch(url).then((response) => {
                if (!response.ok) { 
                    return Promise.reject(response); 
                }
                return response.json();
            }).then((data) => {
                return data;
            }).catch((error) => {
                console.error('Error fetching webshop info:', error);
            });
        },
        checkPaymentMethodActivation(paymentMethodTechnicalName) {
            const paymentMethodRepository = this.repositoryFactory.create('payment_method');

            const criteria = new Shopware.Data.Criteria();
            criteria.addFilter(
                Shopware.Data.Criteria.equals('technicalName', paymentMethodTechnicalName)
            );

            paymentMethodRepository.search(criteria, Shopware.Context.api).then((result) => {
                if (result.total > 0) {
                    const paymentMethod = result.first();
                    this.paymentMethodStatuses[paymentMethodTechnicalName] = paymentMethod.active;
                } else {
                    console.error('Payment method not found');
                }
            }).catch((error) => {
                console.error('Error fetching payment method:', error);
            });
        }
    }
});
