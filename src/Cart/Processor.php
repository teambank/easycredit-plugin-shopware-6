<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class Processor implements CartProcessorInterface
{
    public const DATA_KEY = 'interest_amount';
    public const LINE_ITEM_TYPE = 'easycredit-interest';

    public function process(CartDataCollection $data, Cart $original, Cart $calculated, SalesChannelContext $context, CartBehavior $behavior): void
    {
        if (!$data->has(self::DATA_KEY)) {
            return;
        }

        $calculated->addLineItems($data->get(self::DATA_KEY));
    }
}
