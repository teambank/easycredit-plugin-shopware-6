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
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;

class TwigExtensions extends AbstractExtension
{
    private PaymentHelper $paymentHelper;

    public function __construct(
        PaymentHelper $paymentHelper
    ) {
        $this->paymentHelper = $paymentHelper;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('easyCreditPaymentType', [$this, 'getPaymentType']),
        ];
    }

    public function getPaymentType(PaymentMethodEntity $payment)
    {
        return $this->paymentHelper
            ->getHandlerByPaymentMethod($payment)
            ->getPaymentType();
    }
}
