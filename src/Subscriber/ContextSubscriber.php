<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Subscriber;

use Netzkollektiv\EasyCredit\Api\Storage;
use Shopware\Core\Checkout\Cart\AbstractCartPersister;
use Shopware\Core\Checkout\Cart\CartCalculator;
use Shopware\Core\Framework\Routing\Event\SalesChannelContextResolvedEvent;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextCreatedEvent;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextRestoredEvent;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextTokenChangeEvent;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContextSubscriber implements EventSubscriberInterface
{
    private Storage $storage;

    public function __construct(
        Storage $storage
    ) {
        $this->storage = $storage;
    }

    public static function getSubscribedEvents()
    {
        return [
            SalesChannelContextCreatedEvent::class => 'onContextCreate',
            SalesChannelContextResolvedEvent::class => 'onContextResolved',
            SalesChannelContextTokenChangeEvent::class => 'onContextTokenChange',
            SalesChannelContextRestoredEvent::class => 'onContextRestored'
        ];
    }

    public function onContextRestored(SalesChannelContextRestoredEvent $event): void
    {
        /*
        $token = $event->getRestoredSalesChannelContext()->getToken();
        $oldToken = $event->getCurrentSalesChannelContext()->getToken();

        $stateData = $this->paymentStateDataService->fetchRedeemedGiftCardsFromContextToken($oldToken);
        foreach ($stateData->getElements() as $statedataArray) {
            $this->paymentStateDataService->updateStateDataContextToken($statedataArray, $token);
        }
        */
    }

    public function onContextTokenChange(SalesChannelContextTokenChangeEvent $event): void
    {
        /*
        $token = $event->getCurrentToken();
        $oldToken = $event->getPreviousToken();

        $stateData = $this->paymentStateDataService->fetchRedeemedGiftCardsFromContextToken($oldToken);

        foreach ($stateData->getElements() as $statedataArray) {
            $this->paymentStateDataService->updateStateDataContextToken($statedataArray, $token);
        }
        */
    }

    public function onContextCreate(SalesChannelContextCreatedEvent $event): void
    {
        $context = $event->getSalesChannelContext();

        $this->storage->initialize($context);
    }

    public function onContextResolved(SalesChannelContextResolvedEvent $event): void
    {
        $context = $event->getSalesChannelContext();

        $this->storage->initialize($context);
    }
}
