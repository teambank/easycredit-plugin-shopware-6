<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Netzkollektiv\EasyCredit\Api\Storage;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Shopware\Storefront\Controller\CartLineItemController;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Netzkollektiv\EasyCredit\Cart\InitError;
use Netzkollektiv\EasyCredit\Service\CustomerService;
use Shopware\Core\Framework\Validation\BuildValidationEvent;

class ExpressCheckoutCartHandler implements EventSubscriberInterface
{
    private CartService $cartService;

    private Storage $storage;

    public function __construct(
        CartService $cartService,
        Storage $storage
    ) {
        $this->cartService = $cartService;
        $this->storage = $storage;
    }


    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => 'clearCart',
            CheckoutCartPageLoadedEvent::class => 'addErrorsToCart',
            'framework.validation.address.create' => 'disableAddressValidation',
            'framework.validation.customer.create' => 'disableCustomerValidation'
        ];
    }

    public function clearCart(ControllerArgumentsEvent $event)
    {
        $controller = $event->getController();
        $arguments = $event->getArguments();
        $request = $event->getRequest();

        if (\is_array($controller)) {
            $controller = $controller[0];
        }
        $cart = $arguments[0] ?? null;

        if (
            !$controller instanceof CartLineItemController ||
            $cart === null ||
            !$request->get('easycredit')
        ) {
            return;
        }

        $this->storage->clear();
        foreach ($cart->getLineItems() as $lineItem) {
            $cart->getLineItems()->removeElement($lineItem);
        }
    }

    public function addErrorsToCart(CheckoutCartPageLoadedEvent $event)
    {

        if ($errorMessage = $this->storage->get('error')) {
            $this->storage->set('error', null)->persist();

            $error = new InitError($errorMessage);
            $event->getPage()->getCart()->getErrors()->add($error);
        }
    }

    public function disableAddressValidation(BuildValidationEvent $event): void
    {
        if (!$event->getContext()->hasExtension(CustomerService::EXPRESS_ACTIVE)) {
            return;
        }

        $event->getDefinition()->set('additionalAddressLine1')
            ->set('additionalAddressLine2')
            ->set('phoneNumber');
    }

    public function disableCustomerValidation(BuildValidationEvent $event): void
    {
        if (!$event->getContext()->hasExtension(CustomerService::EXPRESS_ACTIVE)) {
            return;
        }

        $event->getDefinition()->set('birthdayDay')
            ->set('birthdayMonth')
            ->set('birthdayYear');
    }
}
