<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Subscriber;

use Netzkollektiv\EasyCredit\Payment\Handler\AbstractHandler as EasyCreditPaymentHandler;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;

class CheckoutValidationSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;

    private PaymentHelper $paymentHelper;

    private IntegrationFactory $integrationFactory;

    public function __construct(
        RequestStack $requestStack,
        PaymentHelper $paymentHelper,
        IntegrationFactory $integrationFactory
    ) {
        $this->requestStack = $requestStack;
        $this->paymentHelper = $paymentHelper;
        $this->integrationFactory = $integrationFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'framework.validation.order.create' => 'validateTransaction',
        ];
    }

    public function validateTransaction(BuildValidationEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $salesChannelContext = $this->getSalesChannelContextFromRequest($request);
        $paymentHandler = $this->paymentHelper->getHandlerByPaymentMethod($salesChannelContext->getPaymentMethod());

        $checkout = $this->integrationFactory->createCheckout(
            $salesChannelContext->getSalesChannel()->getId()
        );

        if ($paymentHandler instanceof EasyCreditPaymentHandler) {
            if (!$checkout->isApproved()) {
                throw new ConstraintViolationException(new ConstraintViolationList([
                    new ConstraintViolation(
                        '',
                        '',
                        [],
                        null,
                        '/easycredit',
                        $salesChannelContext->getPaymentMethod()->getName(),
                        null,
                        'EASYCREDIT_TRANSATION_NOT_APRROVED'
                    )
                ]), $request->request->all());
            }
        }
    }

    private function getSalesChannelContextFromRequest(Request $request): SalesChannelContext
    {
        return $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
    }
}
