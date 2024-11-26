<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Service;

use Shopware\Core\Framework\Rule\Collector\RuleConditionRegistry;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Checkout\Cart\Rule\CartRuleScope;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;

class RuleEvaluator {

    private EntityRepository $ruleRepository;

    private SettingsServiceInterface $settingsService;

    private CartService $cartService;

    public function __construct(
        EntityRepository $ruleRepository,
        SettingsServiceInterface $settingsService,
        CartService $cartService
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->settingsService = $settingsService;
        $this->cartService = $cartService;
    }

    public function evaluateRule($rule, Cart $cart, SalesChannelContext $salesChannelContext): bool
    {
        $scope = new CartRuleScope($cart, $salesChannelContext);
        return $rule->getPayload()->match($scope);
    }

    public function getCartForProduct ($product, SalesChannelContext $salesChannelContext, $quantity = 1) {
        $reflection = new \ReflectionClass(Cart::class);
        $cart = $reflection->getConstructor()->getNumberOfParameters() == 2 ?
           new Cart('temporaryCart', $salesChannelContext->getToken()):
           new Cart($salesChannelContext->getToken());

        $lineItem = (new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $product->getId(), $quantity))
            ->setGood(true)
            ->setRemovable(true)
            ->setStackable(true);
        return $this->cartService->add($cart, $lineItem, $salesChannelContext);
    }
}
