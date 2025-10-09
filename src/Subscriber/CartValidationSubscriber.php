<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Subscriber;

use Netzkollektiv\EasyCredit\Payment\Handler\AbstractHandler;
use Netzkollektiv\EasyCredit\Payment\Handler\AbstractSynchronousHandler;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Cart\ValidationError;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartValidationSubscriber implements EventSubscriberInterface
{
    private PaymentHelper $paymentHelper;

    private IntegrationFactory $integrationFactory;

    public function __construct(
        PaymentHelper $paymentHelper,
        IntegrationFactory $integrationFactory
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->integrationFactory = $integrationFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware\Core\Checkout\Cart\CartValidatorInterface::validate' => 'validateEasyCreditTransaction',
        ];
    }

    public function validateEasyCreditTransaction(Cart $cart, ErrorCollection $errors, SalesChannelContext $salesChannelContext): void
    {
        $paymentHandler = $this->paymentHelper->getHandlerByPaymentMethod($salesChannelContext->getPaymentMethod());

        if (
            !($paymentHandler instanceof AbstractHandler || 
            $paymentHandler instanceof AbstractSynchronousHandler)
        ) {
            return;
        }

        try {
            $checkout = $this->integrationFactory->createCheckout(
                $salesChannelContext->getSalesChannel()->getId()
            );

            if (!$checkout->isApproved()) {
                $error = new ValidationError('EASYCREDIT_TRANSATION_NOT_APRROVED');
                $errors->add($error);
            }
        } catch (\Throwable $e) {
            // Log error if needed
            return;
        }
    }
} 