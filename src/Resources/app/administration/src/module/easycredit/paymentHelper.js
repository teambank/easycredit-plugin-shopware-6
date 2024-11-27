const easycreditFormattedHandlerPrefix = 'handler_netzkollektiv_';

export const isEasyCreditMethod = (paymentMethod) => {
    return paymentMethod.formattedHandlerIdentifier.startsWith(easycreditFormattedHandlerPrefix)
}