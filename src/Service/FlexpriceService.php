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
use Shopware\Core\Checkout\Cart\CartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;

class FlexpriceService {

    private EntityRepository $ruleRepository;

    private SettingsServiceInterface $settingsService;

    private $cartService;

    public function __construct(
        EntityRepository $ruleRepository,
        SettingsServiceInterface $settingsService,
        $cartService
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->settingsService = $settingsService;
        $this->cartService = $cartService;
    }

    public function isEnabled(SalesChannelContext $salesChannelContext) {
        $webshopInfo = $this->settingsService
            ->getSettings($salesChannelContext->getSalesChannel()->getId())
            ->getWebshopInfo();
        return \is_array($webshopInfo) && isset($webshopInfo['flexprice']) && $webshopInfo['flexprice'] === true;
    }

    protected function getFlexpriceRule(Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('moduleTypes.types', 'easycredit-flexprice'));
        $rule = $this->ruleRepository->search($criteria, $context)->first();
        return $rule;
    }

    protected function evaluateRule($rule, Cart $cart, SalesChannelContext $salesChannelContext): bool
    {
        return $rule->getPayload()->match(new CartRuleScope($cart, $salesChannelContext));
    }

    protected function getCartForProduct (SalesChannelContext $salesChannelContext, $product, $quantity = 1) {
        $reflection = new \ReflectionClass(Cart::class);
        $cart = $reflection->getConstructor()->getNumberOfParameters() == 2 ?
           new Cart('temporaryCart', $salesChannelContext->getToken()):
           new Cart($salesChannelContext->getToken());

        $lineItem = (new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $product->getId(), $quantity))
            ->setGood(true)
            ->setRemovable(true)
            ->setStackable(true);
        $cart = $this->cartService->add($cart, $lineItem, $salesChannelContext);
        return $cart;
    }

    public function shouldDisableFlexprice (SalesChannelContext $salesChannelContext, Cart $cart) {
        if (!$this->isEnabled($salesChannelContext)) {
            return false;
        }

        $evaluated = $this->evaluateRule(
            $this->getFlexpriceRule($salesChannelContext->getContext()),
            $cart,
            $salesChannelContext
        );
        return $evaluated;
    }

    public function shouldDisableFlexpriceForProduct(SalesChannelContext $salesChannelContext, SalesChannelProductEntity $product, $quantity = 1) {
        if (!$this->isEnabled($salesChannelContext)) {
            return false;
        }

        $cart = $this->getCartForProduct($salesChannelContext, $product, $quantity);
        return $this->shouldDisableFlexprice($salesChannelContext, $cart);
    }
}
