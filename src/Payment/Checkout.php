<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Helper\Quote as QuoteHelper;
use Netzkollektiv\EasyCredit\Setting\Exception\SettingsInvalidException;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;
use Netzkollektiv\EasyCredit\Cart\InterestError;
use Netzkollektiv\EasyCredit\Service\FlexpriceService;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Core\Content\Product\State;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class Checkout implements EventSubscriberInterface
{
    private PaymentHelper $paymentHelper;

    private SettingsServiceInterface $settings;

    private IntegrationFactory $integrationFactory;

    private QuoteHelper $quoteHelper;

    private Storage $storage;

    private FlexpriceService $flexpriceService;

    private $cache;

    private $logger;

    public function __construct(
        PaymentHelper $paymentHelper,
        SettingsServiceInterface $settingsService,
        IntegrationFactory $integrationFactory,
        QuoteHelper $quoteHelper,
        Storage $storage,
        FlexpriceService $flexpriceService,
        LoggerInterface $logger,
        TagAwareAdapterInterface $cache
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->settings = $settingsService;
        $this->integrationFactory = $integrationFactory;
        $this->quoteHelper = $quoteHelper;
        $this->storage = $storage;
        $this->flexpriceService = $flexpriceService;
        $this->logger = $logger;
        $this->cache = $cache;
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
        if ($this->storage->get('redirect_url') 
            || $this->storage->get('init')
        ) {
            return;
        }

        $salesChannelContext = $event->getSalesChannelContext();
        $context = $event->getContext();
        $cart = $event->getPage()->getCart();

        $paymentMethodId = $this->paymentHelper->getPaymentMethodId($salesChannelContext->getContext());

        if (!$event->getPage()->getPaymentMethods()->get($paymentMethodId)) {
            return;
        }

        $error = null;
        if ($this->storage->get('error')) {
            $error = $this->storage->get('error');
            $this->storage->set('error', null);
        }

        foreach ($cart->getErrors()->getElements() as $cartError) {
            if ($cartError instanceof InterestError) {
                $this->storage->clear();
            }
        }

        try {
            $settings = $this->settings->getSettings($salesChannelContext->getSalesChannel()->getId());
            $checkout = $this->integrationFactory->createCheckout($salesChannelContext);
        } catch (SettingsInvalidException $e) {
            $this->removePaymentMethodFromConfirmPage($event);

            return;
        }

        try {
            $this->getWebshopDetails($checkout);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->removePaymentMethodFromConfirmPage($event);

            return;
        }

        $isSelected = $paymentMethodId === $salesChannelContext->getPaymentMethod()->getId();
        if ($isSelected && !$this->storage->get('payment_plan')) {
            if ($error === null) {
                try {
                    $quote = $this->quoteHelper->getQuote($cart, $salesChannelContext);
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        if ($this->storage->get('express-ui')) {
            $event->getPage()->setPaymentMethods(
                $event->getPage()->getPaymentMethods()->filter(fn(PaymentMethodEntity $paymentMethod) => $paymentMethod->getId() === $paymentMethodId)
            );
        }

        $event->getPage()->addExtension('easycredit', (new CheckoutData())->assign([
            'isPrefixValid' => isset($quote) ? $checkout->isPrefixValid($quote->getCustomer()->getGender()) : false,
            'grandTotal' => isset($quote) ? $quote->getOrderDetails()->getOrderValue() : null,
            'paymentMethodId' => $paymentMethodId,
            'isSelected' => $isSelected,
            'paymentPlan' => $this->buildPaymentPlan($this->storage->get('summary')),
            'disableFlexprice' => $this->flexpriceService->shouldDisableFlexprice($salesChannelContext, $cart),
            'error' => $error,
            'webshopId' => $settings->getWebshopId()
        ]));
    }

    protected function buildPaymentPlan($summary) {
        $summary = \json_decode((string)$summary);
        if ($summary === false || $summary === null) {
            return null;
        }
        return \json_encode($summary);
    }

    public function getWebshopDetails($checkout) {
        $agreement = [];
        $cacheItem = $this->cache->getItem('easycredit-webshop-details');
        if ($cacheItem->isHit() && $cacheItem->get()) {
            $agreement = $cacheItem->get();
        } else {
            $agreement = $checkout->getWebshopDetails();
            $cacheItem->set($agreement);
            $this->cache->save($cacheItem);
        }
        return $agreement;    
    }

    private function removePaymentMethodFromConfirmPage(CheckoutConfirmPageLoadedEvent $event): void
    {
        $paymentMethodCollection = $event->getPage()->getPaymentMethods();
        $paymentMethodCollection->remove($this->paymentHelper->getPaymentMethodId($event->getContext()));
    }
}
