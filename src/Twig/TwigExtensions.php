<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Service\PaymentAvailability;
use Netzkollektiv\EasyCredit\Service\FlexpriceService;

class TwigExtensions extends AbstractExtension
{
    private PaymentAvailability $paymentAvailabilityService;
    private PaymentHelper $paymentHelper;
    private FlexpriceService $flexpriceService;

    public function __construct(
        PaymentHelper $paymentHelper,
        PaymentAvailability $paymentAvailabilityService,
        FlexpriceService $flexpriceService
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->paymentAvailabilityService = $paymentAvailabilityService;
        $this->flexpriceService = $flexpriceService;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('easyCreditPaymentType', [$this, 'getPaymentType']),
            new TwigFunction('easyCreditAvailablePaymentTypes', [$this, 'getAvailablePaymentTypes']),
            new TwigFunction('easyCreditShouldDisableFlexprice', [$this, 'shouldDisableFlexprice']),
        ];
    }

    public function getPaymentType(PaymentMethodEntity $payment)
    {
        return $this->paymentHelper
            ->getHandlerByPaymentMethod($payment)
            ->getPaymentType();
    }

    public function getAvailablePaymentTypes($salesChannelContext, $product = null) {
        $paymentTypes = $this->paymentAvailabilityService->getAvailablePaymentTypes($salesChannelContext, $product);
        return $paymentTypes;
    }

    public function shouldDisableFlexprice($salesChannelContext, $product = null) {
        return $this->flexpriceService->shouldDisableFlexpriceForProduct($salesChannelContext, $product);
    }
}
