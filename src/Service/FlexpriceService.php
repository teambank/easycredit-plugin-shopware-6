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
use Netzkollektiv\EasyCredit\Service\RuleEvaluator;
use Netzkollektiv\EasyCredit\Service\WebshopInfoService;

class FlexpriceService {

    private EntityRepository $ruleRepository;

    private RuleEvaluator $ruleEvaluator;

    private WebshopInfoService $webshopInfoService;

    public function __construct(
        EntityRepository $ruleRepository,
        RuleEvaluator $ruleEvaluator,
        WebshopInfoService $webshopInfoService
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->ruleEvaluator = $ruleEvaluator;
        $this->webshopInfoService = $webshopInfoService;
    }

    public function isEnabled(SalesChannelContext $salesChannelContext) {
        try {
            return $this->webshopInfoService
                ->getWebshopInfo($salesChannelContext->getSalesChannel()->getId())
                ->getFlexprice() === true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function getFlexpriceRule(Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('moduleTypes.types', 'easycredit-flexprice'));
        $rule = $this->ruleRepository->search($criteria, $context)->first();
        return $rule;
    }

    public function shouldDisableFlexprice (SalesChannelContext $salesChannelContext, Cart $cart) {
        if (!$this->isEnabled($salesChannelContext)) {
            return false;
        }

        $rule = $this->getFlexpriceRule($salesChannelContext->getContext());
        if (!$rule) {
            return false;
        }

        return $this->ruleEvaluator->evaluateRule(
            $rule,
            $cart,
            $salesChannelContext
        );
    }

    public function shouldDisableFlexpriceForProduct(SalesChannelContext $salesChannelContext, SalesChannelProductEntity $product, $quantity = 1) {
        if (!$this->isEnabled($salesChannelContext)) {
            return false;
        }

        $cart = $this->ruleEvaluator->getCartForProduct($product, $salesChannelContext, $quantity);
        return $this->shouldDisableFlexprice($salesChannelContext, $cart);
    }
}
