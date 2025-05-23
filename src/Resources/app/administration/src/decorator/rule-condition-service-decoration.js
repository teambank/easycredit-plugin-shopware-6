Shopware.Application.addServiceProviderDecorator('ruleConditionDataProviderService', (ruleConditionService) => {
    ruleConditionService.addCondition('cartCartAmountWithoutInterest', {
        component: 'sw-condition-cart-amount',
        label: 'easycredit.rule.cartAmountRule',
        scopes: ['cart'],
        group: 'cart',
    });
    ruleConditionService.addCondition('cartPositionPriceWithoutInterest', {
        component: 'sw-condition-cart-position-price',
        label: 'easycredit.rule.cartPositionPrice',
        scopes: ['cart'],
        group: 'cart',
    });
    ruleConditionService.addModuleType({
            id: 'easycredit-flexprice',
            name: 'easycredit.rule.flexPriceType',
    });
    return ruleConditionService;
});