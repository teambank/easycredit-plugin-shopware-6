<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment;

use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractPaymentRoute
{
    abstract public function getDecorated(): AbstractPaymentRoute;

    abstract public function initPayment(Request $request, SalesChannelContext $salesChannelContext): PaymentRouteResponse;

    abstract public function returnFromPaymentPage(Request $request, SalesChannelContext $salesChannelContext): PaymentRouteResponse;
}
