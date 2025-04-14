<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment\Handler;

if (\class_exists('Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler')) {
    class InstallmentPaymentHandler extends AbstractHandler
    {
        public function getPaymentType()
        {
            return 'INSTALLMENT';
        }
    }
} else { // <= SW 6.6
    class InstallmentPaymentHandler extends AbstractSynchronousHandler
    {
        public function getPaymentType()
        {
            return 'INSTALLMENT';
        }
    }
}
