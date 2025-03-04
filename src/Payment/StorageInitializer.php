<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextCreatedEvent;
use Netzkollektiv\EasyCredit\Api\Storage;

class StorageInitializer implements EventSubscriberInterface
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
            SalesChannelContextCreatedEvent::class => 'onContextCreate'
        ];
    }

    public function onContextCreate(SalesChannelContextCreatedEvent $event): void
    {
        $context = $event->getSalesChannelContext();

        $this->storage->initialize($context);
    }
}
