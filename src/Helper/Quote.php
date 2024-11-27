<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Helper;

use Netzkollektiv\EasyCredit\Api;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Netzkollektiv\EasyCredit\Api\QuoteInvalidException;
use Netzkollektiv\EasyCredit\Api\QuoteBuilder;
use Netzkollektiv\EasyCredit\Api\OrderBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Teambank\EasyCreditApiV3\Model\Transaction;

class Quote
{
    private QuoteBuilder $quoteBuilder;

    private OrderBuilder $orderBuilder;

    public function __construct(
        QuoteBuilder $quoteBuilder,
        OrderBuilder $orderBuilder
    ) {
        $this->quoteBuilder = $quoteBuilder;
        $this->orderBuilder = $orderBuilder;
    }

    /**
     * @param Cart|\Shopware\Core\Checkout\Order\OrderEntity|null $cart
     */
    public function getQuote($cart, SalesChannelContext $salesChannelContext): Transaction
    {
        if ($cart instanceof Cart) {
            return $this->quoteBuilder->build($cart, $salesChannelContext);
        }
        return $this->orderBuilder->build($cart, $salesChannelContext);
    }
}
