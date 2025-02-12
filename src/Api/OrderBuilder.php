<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use Netzkollektiv\EasyCredit\Helper\MetaDataProvider;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Teambank\EasyCreditApiV3\Model\ShippingAddress;
use Teambank\EasyCreditApiV3\Model\InvoiceAddress;

class OrderBuilder extends QuoteBuilder
{
    public function getId(): ?string
    {
        return '';
    }

    public function getShippingMethod(): ?string
    {
        return '';
    }

    public function getIsClickAndCollect(): Bool
    {
        return false;
    }

    public function getDuration(): ?string
    {
        return null;
    }

    public function getInvoiceAddress(): ?InvoiceAddress
    {
        return null;
    }

    public function getShippingAddress(): ShippingAddress
    {
        $deliveries = $this->cart->getDeliveries();
        if (
            $deliveries->first() === null
            || $deliveries->first()->getShippingOrderAddress() === null
        ) {
            throw new QuoteInvalidException('quote invalid');
        }

        return $this->addressBuilder->build($deliveries->first()->getShippingOrderAddress());
    }

    public function getCustomer()
    {
        return null;
    }
}
