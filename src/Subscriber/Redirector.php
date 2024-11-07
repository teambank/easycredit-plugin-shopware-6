<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Subscriber;

use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextSwitchEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use Teambank\RatenkaufByEasyCreditApiV3\Integration\ValidationException;
use Teambank\RatenkaufByEasyCreditApiV3\ApiException;

class Redirector implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \Symfony\Component\HttpFoundation\Request|null
     */
    private $request;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var PaymentHelper
     */
    private PaymentHelper $paymentHelper;

    private Storage $storage;

    public function __construct(
        ContainerInterface $container,
        RequestStack $requestStack,
        UrlGeneratorInterface $router,
        PaymentHelper $paymentHelper,
        Storage $storage
    ) {
        $this->container = $container;
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
        $this->paymentHelper = $paymentHelper;
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
        $salesChannelContext = $event->getSalesChannelContext();
        $attributes = (isset($this->request->attributes)) ? $this->request->attributes : null;

        if (!$this->isRoute('frontend.checkout.configure', $this->request)) {
            return;
        }

        if (!$event->getRequestDataBag()->get('paymentMethodId')
            || !$this->paymentHelper->isSelected($salesChannelContext, $event->getRequestDataBag()->get('paymentMethodId'))
        ) {
            return;
        }

        if (\version_compare($this->container->getParameter('kernel.shopware_version'), '6.4.0', '>=')
            && !$event->getRequestDataBag()->get('easycredit')
        ) {
            return;
        }

        $this->storage
            ->set('express', false)
            ->set('duration', $event->getRequestDataBag()->get('easycredit')->get('number-of-installments'))
            ->set('init', true);
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        if (!$this->storage->get('init')) {
            return;
        }

        $salesChannelContext = $event->getSalesChannelContext();

        $this->storage->set('init', false);
        $this->storage->set('cartToken', $event->getPage()->getCart()->getToken());

        $this->paymentHelper->startCheckout($salesChannelContext);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {       
        if (!$this->request->hasSession()) {
            return; // do not run in CLI & API
        }

        if ($redirectUrl = $this->storage->get('redirect_url')) {
            $event->setResponse(new RedirectResponse($redirectUrl));
            $this->storage->set('redirect_url', null);
        }
    }

    protected function isRoute($route, $request)
    {
        $attributes = (isset($request->attributes)) ? $request->attributes : null;

        if ($attributes === null
            || $attributes->get('_route') !== $route
        ) {
            return false;
        }

        return true;
    }
}
