<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Service;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Service\RuleEvaluator;
use Netzkollektiv\EasyCredit\Setting\Exception\SettingsInvalidException;

class PaymentAvailability {

    private EntityRepository $ruleRepository;

    private CartService $cartService;

    private SettingsServiceInterface $settingsService;

    private PaymentHelper $paymentHelper;

    private RuleEvaluator $ruleEvaluator;

    private array $cachedPaymentTypes = [];

    public function __construct(
        EntityRepository $ruleRepository,
        CartService $cartService,
        SettingsServiceInterface $settingsService,
        PaymentHelper $paymentHelper,
        RuleEvaluator $ruleEvaluator
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->cartService = $cartService;
        $this->settingsService = $settingsService;
        $this->paymentHelper = $paymentHelper;
        $this->ruleEvaluator = $ruleEvaluator;
    }

    public function getAvailablePaymentTypes($salesChannelContext, $product = null): array {
        $cacheKey = $this->getCacheKey($salesChannelContext, $product);

        if (isset($this->cachedPaymentTypes[$cacheKey])) {
            return $this->cachedPaymentTypes[$cacheKey];
        }

        $paymentMethods = $this->paymentHelper->getActivePaymentMethods($salesChannelContext);
        if (!$paymentMethods) {
            return [];
        }

        if (!$this->getSettings($salesChannelContext)) {
            return [];
        }

        if ($product !== null) {
            $cart = $this->ruleEvaluator->getCartForProduct($product, $salesChannelContext);
        } else {
            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        }

        $this->cachedPaymentTypes[$cacheKey] = \array_filter($paymentMethods->map(function ($paymentMethod) use ($salesChannelContext, $cart) {
            $available = $this->ruleEvaluator->evaluateRule(
                $this->getAvailabilityRules($salesChannelContext->getContext())
                    ->filter(fn($rule) => $rule->getId() === $paymentMethod->getAvailabilityRuleId())->first(),
                $cart,
                $salesChannelContext
            );
            return $available ? $this->paymentHelper->getHandlerByPaymentMethod($paymentMethod)->getPaymentType() : null;
        }));

        return $this->cachedPaymentTypes[$cacheKey];
    }

    private function getCacheKey (SalesChannelContext $salesChannelContext, $product) {
        $cacheKey = [$salesChannelContext->getToken()];
        if ($product) {
            $cacheKey[] = $product->getId();
        }
        return \implode('-', $cacheKey);
    }

    private function getSettings(SalesChannelContext $salesChannelContext)
    {
        try {
            $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        } catch (SettingsInvalidException $e) {
            return false;
        }

        return true;
    }

    private $availibilityRules = null;

    private function getAvailabilityRules($context) {
        if ($this->availibilityRules === null) {
            $ids = $this->paymentHelper->getEasyCreditMethods($context)->map(fn ($method) => $method->getAvailabilityRuleId());
            $this->availibilityRules = $this->ruleRepository->search(
                new Criteria($ids),
                $context
            );
        }
        return $this->availibilityRules;
    }
}
