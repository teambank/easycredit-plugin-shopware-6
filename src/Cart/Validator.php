<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Cart;

use Monolog\Logger;
use Symfony\Component\HttpFoundation\RequestStack;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Cart\Order\IdStruct;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\EasyCreditRatenkauf;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Helper\Quote as QuoteHelper;
use Teambank\EasyCreditApiV3\Integration\Checkout;
use Teambank\EasyCreditApiV3\Model\Transaction;

class Validator implements CartValidatorInterface
{
    protected $integrationFactory;

    protected $quoteHelper;

    protected $paymentHelper;

    protected $storage;

    protected $logger;

    protected $requestStack;

    public function __construct(
        IntegrationFactory $integrationFactory,
        QuoteHelper $quoteHelper,
        PaymentHelper $paymentHelper,
        Storage $storage,
        Logger $logger,
        RequestStack $requestStack
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->quoteHelper = $quoteHelper;
        $this->paymentHelper = $paymentHelper;
        $this->storage = $storage;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
    }

    public function validate(
        Cart $cart,
        ErrorCollection $errors,
        SalesChannelContext $salesChannelContext
    ): void {
        if (!$this->shouldValidate($cart)) {
            return;
        }

        if (!$this->paymentHelper->isSelected($salesChannelContext)) {
            $this->resetPaymentState();

            return;
        }

        if (!$this->quoteHelper->supportsCart($cart, $salesChannelContext)) {
            $this->resetPaymentState();

            return;
        }

        $checkout = $this->integrationFactory->tryCreateCheckout(
            $salesChannelContext->getSalesChannel()->getId()
        );
        if ($checkout === null) {
            $this->resetPaymentState();

            return;
        }

        $quote = $this->quoteHelper->getQuote($salesChannelContext, $cart);

        $this->validateActiveTransaction($checkout, $quote, $errors);
    }

    private function shouldValidate(Cart $cart): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return false;
        }

        if (isset($request->attributes) && !$request->attributes->get('_route')) {
            return false;
        }

        if ($request->attributes->get('_route') === 'frontend.easycredit.return') {
            return false;
        }

        if (\method_exists($cart, 'getName') && \in_array($cart->getName(), ['recalculation', 'sales-channel'])) {
            return false;
        }

        if ($cart->getExtensionOfType(OrderConverter::ORIGINAL_ORDER_NUMBER, IdStruct::class)) {
            return false;
        }

        if ($cart->getToken() !== $this->storage->get('cartToken')) {
            return false;
        }

        if ($this->storage->get('express')) {
            return false;
        }

        if ($this->isAfterCheckoutOrderPlaced()) {
            return false;
        }

        return true;
    }

    private function validateActiveTransaction(
        Checkout $checkout,
        Transaction $quote,
        ErrorCollection $errors
    ): void {
        if ($this->storage->get('payment_type') !== $quote->getPaymentType()) {
            $this->resetPaymentState();

            return;
        }

        if (!$checkout->isAmountValid($quote)) {
            $this->syncQuoteAmount($checkout, $quote, $errors);

            return;
        }

        if (!$checkout->verifyAddress($quote)) {
            $this->logger->debug('InterestError: address changed');
            $errors->add(new InterestError());
        }
    }

    private function syncQuoteAmount(
        Checkout $checkout,
        Transaction $quote,
        ErrorCollection $errors
    ): void {
        try {
            $checkout->update($quote);
            $this->storage->persist();
        } catch (\Throwable $e) {
            $this->logger->debug('InterestError: amount not valid' . $e->getMessage());
            $this->resetPaymentState();
            $errors->add(new InterestError());
        }
    }

    private function resetPaymentState(): void
    {
        $this->storage->clear();
    }

    private function isAfterCheckoutOrderPlaced(): bool
    {
        return (bool) $this->requestStack->getCurrentRequest()?->attributes->get(
            EasyCreditRatenkauf::CHECKOUT_ORDER_PLACED_REQUEST_ATTR
        );
    }
}
