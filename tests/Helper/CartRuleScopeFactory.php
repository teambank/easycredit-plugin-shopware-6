<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Test\Helper;

use Netzkollektiv\EasyCredit\Cart\Processor;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Rule\CartRuleScope;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\CheckoutRuleScope;
use Shopware\Core\Framework\Rule\RuleScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

final class CartRuleScopeFactory
{
    public static function create(
        float $totalPrice,
        float $positionPrice,
        ?float $interestAmount = null
    ): CartRuleScope {
        $cart = new Cart('easycredit-test-cart');
        $cart->setPrice(new CartPrice(
            $totalPrice,
            $totalPrice,
            $positionPrice,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        ));

        if ($interestAmount !== null) {
            $interestLineItem = new LineItem('easycredit-interest', Processor::LINE_ITEM_TYPE, null, 1);
            $interestLineItem->setPrice(new CalculatedPrice(
                $interestAmount,
                $interestAmount,
                new CalculatedTaxCollection(),
                new TaxRuleCollection()
            ));
            $cart->add($interestLineItem);
        }

        return new CartRuleScope($cart, MockFactory::create(SalesChannelContext::class));
    }

    public static function createNonCartScope(): RuleScope
    {
        return new CheckoutRuleScope(MockFactory::create(SalesChannelContext::class));
    }
}
