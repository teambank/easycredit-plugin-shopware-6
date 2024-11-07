<?php declare(strict_types=1);
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
use Netzkollektiv\EasyCredit\Api\QuoteInvalidException;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Helper\Quote as QuoteHelper;

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
        if (!$this->requestStack->getCurrentRequest()) {
            return; // do not run in CLI
        }

        if (
            isset($this->requestStack->getCurrentRequest()->attributes) &&
            !$this->requestStack->getCurrentRequest()->attributes->get('_route') // if route is not set no controller was resolved (leading to 404) and the cart is empty
        ) {
            return;
        }

        if (\method_exists($cart, 'getName') && \in_array($cart->getName(), ['recalculation', 'sales-channel'])) { // skip validation on recalculation (SW <= 6.4)
            return;
        }

        if ($cart->getExtensionOfType(OrderConverter::ORIGINAL_ORDER_NUMBER, IdStruct::class)) { // skip validation on recalculation
            return;
        }

        if ($cart->getToken() !== $this->storage->get('cartToken')) {
            return;
        }

        if ($this->storage->get('express')) { // skip validation during express initialization
            return;
        }

        if (!$this->paymentHelper->isSelected($salesChannelContext)) {
            $this->storage->clear();

            return;
        }

        try {
            $checkout = $this->integrationFactory->createCheckout(
                $salesChannelContext
            );
        } catch (\Throwable $e) {
            $this->storage->clear();

            return;
        }

        try {
            $quote = $this->quoteHelper->getQuote($cart, $salesChannelContext);
        } catch (QuoteInvalidException $e) {
            $this->storage->clear();

            return;
        }

        if ($this->storage->get('interest_amount') === null) {
            $this->logger->debug('InterestError: interest amount not set'); 
            $errors->add(new InterestError());

            return;
        }
        if (!$checkout->isAmountValid($quote)) {
            try {
                $checkout->update($quote);
            } catch (\Throwable $e) {
                $this->logger->debug('InterestError: amount not valid'. $e->getMessage());
                $this->storage->clear();
                $errors->add(new InterestError());
            }
            return;
        }
        if (!$checkout->verifyAddress($quote)) {
            $this->logger->debug('InterestError: address changed');
            $errors->add(new InterestError());

            return;
        }
    }
}
