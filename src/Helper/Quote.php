<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Helper;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Teambank\EasyCreditApiV3\Model\Transaction;
use Netzkollektiv\EasyCredit\Api\QuoteBuilder;
use Netzkollektiv\EasyCredit\Api\OrderBuilder;

class Quote
{
    private CartService $cartService;

    private QuoteBuilder $quoteBuilder;

    private OrderBuilder $orderBuilder;

    public function __construct(
        CartService $cartService,
        QuoteBuilder $quoteBuilder,
        OrderBuilder $orderBuilder
    ) {
        $this->quoteBuilder = $quoteBuilder;
        $this->orderBuilder = $orderBuilder;
        $this->cartService = $cartService;
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @param Cart|\Shopware\Core\Checkout\Order\OrderEntity|null $cart
     */
    public function getQuote(SalesChannelContext $salesChannelContext, $cart = null): Transaction
    {
        if ($cart === null) {
            $cart = $this->getUncachedCart($salesChannelContext);
        }
        if ($cart instanceof Cart) {
            return $this->quoteBuilder->build($cart, $salesChannelContext);
        }
        return $this->orderBuilder->build($cart, $salesChannelContext);
    }

    public function getUncachedCart($salesChannelContext)
    {
        $reflectionMethod = new \ReflectionMethod($this->cartService, 'getCart');
        $parameters = $reflectionMethod->getParameters();
        $paramNames = [];
        foreach ($parameters as $parameter) {
            $paramNames[$parameter->getName()] = $parameter;
        }
        if (isset($paramNames['taxed']) && $paramNames['taxed']->hasType() && $paramNames['taxed']->getType() == 'bool') {
            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext, false);
        } elseif (isset($paramNames['name']) && $paramNames['name']->hasType() && $paramNames['name']->getType() == 'string') {
            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext, 'sales-channel', false);
        } else {
            throw new \LogicException('Unknown getCart method signature.');
        }
        return $cart;
    }
}
