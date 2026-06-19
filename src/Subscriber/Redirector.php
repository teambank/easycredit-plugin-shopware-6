<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Subscriber;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextSwitchEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Service\CheckoutService;
use Netzkollektiv\EasyCredit\Helper\Quote as QuoteHelper;
use Netzkollektiv\EasyCredit\Util\RedirectUrlValidator;

class Redirector implements EventSubscriberInterface
{
    private ContainerInterface $container;

    private RequestStack $requestStack;

    private PaymentHelper $paymentHelper;

    private QuoteHelper $quoteHelper;

    private CheckoutService $checkoutService;

    private Storage $storage;

    public function __construct(
        ContainerInterface $container,
        RequestStack $requestStack,
        PaymentHelper $paymentHelper,
        QuoteHelper $quoteHelper,
        CheckoutService $checkoutService,
        Storage $storage
    ) {
        $this->container = $container;
        $this->requestStack = $requestStack;
        $this->paymentHelper = $paymentHelper;
        $this->quoteHelper = $quoteHelper;
        $this->checkoutService = $checkoutService;
        $this->storage = $storage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SalesChannelContextSwitchEvent::class => 'onSalesChannelContextSwitch',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onSalesChannelContextSwitch(SalesChannelContextSwitchEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $salesChannelContext = $event->getSalesChannelContext();

        if (!$this->isRoute('frontend.checkout.configure', $request)) {
            return;
        }

        if (
            !$event->getRequestDataBag()->get('paymentMethodId')
            || !$this->paymentHelper->isSelected($salesChannelContext, $event->getRequestDataBag()->get('paymentMethodId'))
        ) {
            return;
        }

        if (
            \version_compare($this->container->getParameter('kernel.shopware_version'), '6.4.0', '>=')
            && !$event->getRequestDataBag()->get('easycredit')
        ) {
            return;
        }

        $this->storage
            ->set('express', false)
            ->set('duration', $event->getRequestDataBag()->get('easycredit')->get('number-of-installments'))
            ->set('init', true)
            ->persist();
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        if (!$this->storage->get('init')) {
            return;
        }

        $salesChannelContext = $event->getSalesChannelContext();
        $cart = $event->getPage()->getCart();

        $this->storage->set('init', false);
        $this->storage->set('cartToken', $cart->getToken());

        $this->checkoutService->startCheckout(
            $salesChannelContext,
            $this->quoteHelper->getQuote($salesChannelContext, $cart)
        );
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasSession() || $this->isStoreApiRequest($request)) {
            return; // do not run in CLI & API
        }

        if ($redirectUrl = $this->storage->get('redirect_url')) {
            if (!RedirectUrlValidator::isEasyCreditHost($redirectUrl)) {
                $this->storage->set('redirect_url', null)->persist();

                return;
            }

            $event->setResponse(new RedirectResponse($redirectUrl));
            $this->storage->set('redirect_url', null)->persist();
        }
    }

    private function isStoreApiRequest(Request $request): bool
    {
        $routeScope = $request->attributes->get('_routeScope');

        if (\is_array($routeScope)) {
            return \in_array('store-api', $routeScope, true);
        }

        if (\is_object($routeScope) && \method_exists($routeScope, 'getScopes')) {
            return \in_array('store-api', $routeScope->getScopes(), true);
        }

        return false;
    }

    protected function isRoute(string $route, Request $request): bool
    {
        $attributes = (isset($request->attributes)) ? $request->attributes : null;

        if (
            $attributes === null
            || $attributes->get('_route') !== $route
        ) {
            return false;
        }

        return true;
    }
}
