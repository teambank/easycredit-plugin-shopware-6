<?php
namespace Netzkollektiv\EasyCredit\Service;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Service\RuleEvaluator;

class PaymentAvailability {

    private EntityRepository $ruleRepository;

    private CartService $cartService;

    private SettingsServiceInterface $settingsService;

    private PaymentHelper $paymentHelper;

    private RuleEvaluator $ruleEvaluator;

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

    public function isAvailable($salesChannelContext, $product = null) {
        if (!$this->paymentHelper->isPaymentMethodInSalesChannel($salesChannelContext)) {
            return false;
        }

        if (!$this->getSettings($salesChannelContext)) {
            return false;
        }

        if ($product !== null) {
            $cart = $this->ruleEvaluator->getCartForProduct($product, $salesChannelContext);
        } else {
            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        }

        $paymentMethod = $this->paymentHelper->getPaymentMethod($salesChannelContext);
        $available = $this->ruleEvaluator->evaluateRule(
            $this->getAvailabilityRule($paymentMethod->getAvailabilityRuleId(), $salesChannelContext->getContext()),
            $cart,
            $salesChannelContext
        );
        return $available;
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

    private function getAvailabilityRule($id, $context) {
        return $this->ruleRepository->search(
            new Criteria([$id]),
            $context
        )->first();
    }
}
