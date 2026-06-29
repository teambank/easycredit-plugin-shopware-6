<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Cart;

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
use Netzkollektiv\EasyCredit\Logger\DebugLogger;
use Teambank\EasyCreditApiV3\Integration\Checkout;
use Teambank\EasyCreditApiV3\Model\Transaction;

class Validator implements CartValidatorInterface
{
    protected $integrationFactory;

    protected $quoteHelper;

    protected $paymentHelper;

    protected $storage;

    protected $debugLogger;

    protected $requestStack;

    public function __construct(
        IntegrationFactory $integrationFactory,
        QuoteHelper $quoteHelper,
        PaymentHelper $paymentHelper,
        Storage $storage,
        DebugLogger $debugLogger,
        RequestStack $requestStack
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->quoteHelper = $quoteHelper;
        $this->paymentHelper = $paymentHelper;
        $this->storage = $storage;
        $this->debugLogger = $debugLogger;
        $this->requestStack = $requestStack;
    }

    public function validate(
        Cart $cart,
        ErrorCollection $errors,
        SalesChannelContext $salesChannelContext
    ): void {
        if (!$this->shouldValidate($cart, $salesChannelContext)) {
            return;
        }

        if (!$this->paymentHelper->isSelected($salesChannelContext)) {
            $this->resetPaymentState('payment not selected', $salesChannelContext);

            return;
        }

        if (!$this->quoteHelper->supportsCart($cart, $salesChannelContext)) {
            $this->resetPaymentState('cart not supported', $salesChannelContext);

            return;
        }

        $checkout = $this->integrationFactory->tryCreateCheckout(
            $salesChannelContext->getSalesChannel()->getId()
        );
        if ($checkout === null) {
            $this->resetPaymentState('checkout unavailable', $salesChannelContext);

            return;
        }

        $quote = $this->quoteHelper->getQuote($salesChannelContext, $cart);

        $this->validateActiveTransaction($checkout, $quote, $errors, $salesChannelContext);
    }

    private function shouldValidate(Cart $cart, SalesChannelContext $salesChannelContext): bool
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

        $storedCartToken = $this->storage->get('cartToken');
        if ($cart->getToken() !== $storedCartToken) {
            if ($storedCartToken !== null) {
                $this->logValidatorSkip(
                    'cartToken mismatch',
                    $salesChannelContext,
                    \sprintf('cart=%s stored=%s', $cart->getToken(), $storedCartToken)
                );
            }

            return false;
        }

        if ($this->storage->get('express')) {
            $this->logValidatorSkip('express checkout active', $salesChannelContext);

            return false;
        }

        if ($this->isAfterCheckoutOrderPlaced()) {
            $this->logValidatorSkip('after order placed', $salesChannelContext);

            return false;
        }

        return true;
    }

    private function validateActiveTransaction(
        Checkout $checkout,
        Transaction $quote,
        ErrorCollection $errors,
        SalesChannelContext $salesChannelContext
    ): void {
        if ($this->storage->get('payment_type') !== $quote->getPaymentType()) {
            $this->resetPaymentState('payment_type mismatch', $salesChannelContext);

            return;
        }

        if (!$checkout->isAmountValid($quote)) {
            $this->syncQuoteAmount($checkout, $quote, $errors, $salesChannelContext);

            return;
        }

        if (!$checkout->verifyAddress($quote)) {
            $this->debugLogger->debug(
                'validator::invalidate address changed',
                $salesChannelContext->getSalesChannelId()
            );
            $errors->add(new InterestError());
        }
    }

    private function syncQuoteAmount(
        Checkout $checkout,
        Transaction $quote,
        ErrorCollection $errors,
        SalesChannelContext $salesChannelContext
    ): void {
        try {
            $checkout->update($quote);
            $this->storage->persist();
        } catch (\Throwable $e) {
            $this->debugLogger->debug(
                'validator::invalidate amount sync failed: ' . $e->getMessage(),
                $salesChannelContext->getSalesChannelId()
            );
            $this->resetPaymentState('amount sync failed', $salesChannelContext);
            $errors->add(new InterestError());
        }
    }

    private function resetPaymentState(string $reason, SalesChannelContext $salesChannelContext): void
    {
        $this->debugLogger->debug(
            'validator::reset ' . $reason,
            $salesChannelContext->getSalesChannelId()
        );
        $this->storage->clear();
    }

    private function logValidatorSkip(
        string $reason,
        SalesChannelContext $salesChannelContext,
        string $detail = ''
    ): void {
        $message = 'validator::skip ' . $reason;
        if ($detail !== '') {
            $message .= ' (' . $detail . ')';
        }

        $this->debugLogger->debug($message, $salesChannelContext->getSalesChannelId());
    }

    private function isAfterCheckoutOrderPlaced(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return false;
        }

        return (bool) $request->attributes->get(
            EasyCreditRatenkauf::CHECKOUT_ORDER_PLACED_REQUEST_ATTR
        );
    }
}
