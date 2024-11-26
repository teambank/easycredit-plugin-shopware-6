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
use Netzkollektiv\EasyCredit\Service\PaymentAvailability;

class TwigExtensions extends AbstractExtension
{
    private PaymentAvailability $paymentAvailabilityService;

    public function __construct(
        PaymentAvailability $paymentAvailabilityService
    ) {
        $this->paymentAvailabilityService = $paymentAvailabilityService;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('easyCreditAvailable', [$this, 'isAvailable']),
        ];
    }

    public function isAvailable($salesChannelContext, $product = null) {
        return $this->paymentAvailabilityService->isAvailable($salesChannelContext, $product);
    }
}