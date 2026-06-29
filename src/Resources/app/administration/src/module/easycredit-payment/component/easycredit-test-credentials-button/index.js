import template from './easycredit-test-credentials-button.html.twig';
import './easycredit.scss';

const { Mixin } = Shopware;

Shopware.Component.register('easycredit-test-credentials-button', {
    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    inject: ['EasyCreditRatenkaufApiCredentialsService'],
    data() {
        return {
            isLoading: false,
            isTesting: false,
            isTestSuccessful: false,
            testButtonDisabled: false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
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
        onTest() {
            this.isTesting = true;

            const salesChannelId = this.getCurrentSalesChannelId();
            const webshopId = this.getConfig(salesChannelId)['EasyCreditRatenkauf.config.webshopId'] ||
                this.getConfig(null)['EasyCreditRatenkauf.settings.webshopId'];
            const apiPassword = this.getConfig(salesChannelId)['EasyCreditRatenkauf.config.apiPassword'] ||
                this.getConfig(null)['EasyCreditRatenkauf.settings.apiPassword'];
            const apiSignature = this.getConfig(salesChannelId)['EasyCreditRatenkauf.config.apiSignature'] ||
                this.getConfig(null)['EasyCreditRatenkauf.settings.apiSignature'];

            this.EasyCreditRatenkaufApiCredentialsService.validateApiCredentials(
                webshopId,
                apiPassword,
                apiSignature
            ).then((response) => {
                const credentialsValid = response.credentialsValid;

                if (credentialsValid) {
                    this.isTesting = false;
                    this.isTestSuccessful = true;
                }
            }).catch((errorResponse) => {
                this.isTesting = false;
                this.isTestSuccessful = false;

                const errors = errorResponse?.response?.data?.errors ?? [];
                const notActive = errors.some((error) => error.code === 'NETZKOLLEKTIV_EASYCREDIT__API_CREDENTIALS_NOT_ACTIVE');

                this.createNotificationError({
                    title: this.$tc('easycredit.settingForm.titleSaveError'),
                    message: this.$tc(
                        notActive
                            ? 'easycredit.settingForm.messageTestErrorNotActive'
                            : 'easycredit.settingForm.messageTestError'
                    )
                });
            });
        }
    }
});