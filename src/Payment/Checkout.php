<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment;

use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Helper\Quote as QuoteHelper;
use Netzkollektiv\EasyCredit\Setting\Exception\SettingsInvalidException;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;
use Netzkollektiv\EasyCredit\Cart\InterestError;
use Netzkollektiv\EasyCredit\Service\FlexpriceService;
use Netzkollektiv\EasyCredit\Payment\Handler\BillPaymentHandler;
use Netzkollektiv\EasyCredit\Payment\Handler\InstallmentPaymentHandler;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Netzkollektiv\EasyCredit\Service\WebshopInfoService;

class Checkout implements EventSubscriberInterface
{
    private PaymentHelper $paymentHelper;

    private SettingsServiceInterface $settings;

    private IntegrationFactory $integrationFactory;

    private QuoteHelper $quoteHelper;

    private Storage $storage;

    private FlexpriceService $flexpriceService;

    private LoggerInterface $logger;

    private WebshopInfoService $webshopInfoService;

    public function __construct(
        Storage $storage,
        PaymentHelper $paymentHelper,
        QuoteHelper $quoteHelper,
        SettingsServiceInterface $settings,
        IntegrationFactory $integrationFactory,
        WebshopInfoService $webshopInfoService,
        LoggerInterface $logger,
        FlexpriceService $flexpriceService
    ) {
        $this->storage = $storage;
        $this->paymentHelper = $paymentHelper;
        $this->quoteHelper = $quoteHelper;
        $this->settings = $settings;
        $this->integrationFactory = $integrationFactory;
        $this->webshopInfoService = $webshopInfoService;
        $this->logger = $logger;
        $this->flexpriceService = $flexpriceService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
        ];
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        if (
            $this->storage->get('redirect_url')
            || $this->storage->get('init')
        ) {
            return;
        }

        $salesChannelContext = $event->getSalesChannelContext();
        $context = $event->getContext();
        $cart = $event->getPage()->getCart();

        if (!$this->paymentHelper->isEasyCreditInSalesChannel($salesChannelContext)) {
            return;
        }

        $error = null;
        if ($this->storage->get('error')) {
            $error = $this->storage->get('error');
            $this->storage->set('error', null)->persist();
        }

        foreach ($cart->getErrors()->getElements() as $cartError) {
            if ($cartError instanceof InterestError) {
                $this->storage->clear();
            }
        }

        try {
            $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
            $settings = $this->settings->getSettings($salesChannelId);
            $checkout = $this->integrationFactory->createCheckout($salesChannelId);
        } catch (SettingsInvalidException $e) {
            $this->removePaymentMethodFromConfirmPage($event);

            return;
        }

        try {
            $this->webshopInfoService->getWebshopInfo($salesChannelId);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->removePaymentMethodFromConfirmPage($event);

            return;
        }

        $isSelected = $this->paymentHelper->isSelected($salesChannelContext);
        if ($isSelected && !$this->storage->get('summary')) {
            if ($error === null) {
                try {
                    $quote = $this->quoteHelper->getQuote($salesChannelContext, $cart);
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        $paymentMethods = $this->paymentHelper->getEasyCreditMethods($context);

        $event->getPage()->addExtension('easycredit', (new CheckoutData())->assign([
            'grandTotal' => isset($quote) ? $quote->getOrderDetails()->getOrderValue() : null,
            'selectedPaymentMethod' => $salesChannelContext->getPaymentMethod()->getId(),
            'paymentMethodIds' => [
                'installmentPaymentId' => $paymentMethods->filterByProperty('handlerIdentifier', InstallmentPaymentHandler::class)->first()->get('id'),
                'billPaymentId' => $paymentMethods->filterByProperty('handlerIdentifier', BillPaymentHandler::class)->first()->get('id')
            ],
            'approved' => $checkout->isApproved(),
            'paymentPlan' => $this->buildPaymentPlan($this->storage->get('summary')),
            'disableFlexprice' => $this->flexpriceService->shouldDisableFlexprice($salesChannelContext, $cart),
            'error' => $error,
            'webshopId' => $settings->getWebshopId()
        ]));
    }

    protected function buildPaymentPlan($summary)
    {
        if (empty($summary)) {
            return null;
        }

        try {
            $decoded = \json_decode((string)$summary, true, 512, JSON_THROW_ON_ERROR);
            return \json_encode($decoded);
        } catch (\JsonException $e) {
            return null;
        }
    }

    private function removePaymentMethodFromConfirmPage(CheckoutConfirmPageLoadedEvent $event): void
    {
        $paymentMethodCollection = $event->getPage()->getPaymentMethods();
        foreach ($this->paymentHelper->getEasyCreditMethods($event->getContext()) as $method) {
            $paymentMethodCollection->remove($method->get('id'));
        }
    }
}
