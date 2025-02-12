<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Subscriber;

use Netzkollektiv\EasyCredit\Cart\Processor;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;
use Psr\Log\LoggerInterface;

class InterestRemover implements EventSubscriberInterface
{
    private SettingsServiceInterface $settings;

    private $connection;

    private Storage $storage;

    private $logger;

    public function __construct(
        SettingsServiceInterface $settingsService,
        Connection $connection,
        Storage $storage,
        LoggerInterface $logger
    ) {
        $this->settings = $settingsService;
        $this->connection = $connection;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'removeInterest',
        ];
    }

    public function removeInterest(CheckoutOrderPlacedEvent $event): void
    {
        $order = $event->getOrder();

        $interestLineItem = $order->getLineItems()
            ->filterByType(Processor::LINE_ITEM_TYPE)
            ->first();

        $isEnabled = $this->settings
            ->getSettings($event->getSalesChannelId(), false)
            ->getRemoveInterest();

        if (!$isEnabled || !$interestLineItem) {
            return;
        }

        $this->connection->beginTransaction();
        try {
            $this->connection->executeStatement("
                UPDATE order_transaction ot
                INNER JOIN order_line_item ol ON ol.order_id = ot.order_id AND ol.type = 'easycredit-interest'
                    Set ot.amount = JSON_REPLACE(ot.amount,
                        '$.unitPrice', ROUND(json_extract(ot.amount, '$.unitPrice') - ol.total_price, 2),
                        '$.totalPrice', ROUND(json_extract(ot.amount, '$.totalPrice') - ol.total_price, 2)
                    )
                WHERE ot.order_id = ?;
            ", [
                Uuid::fromHexToBytes($order->getId())
            ]);

            $this->connection->executeStatement("
                UPDATE `order` o
                INNER JOIN order_line_item ol ON ol.order_id = o.id AND ol.type = 'easycredit-interest'
                Set 
                    o.price = JSON_REPLACE(o.price, 
                        '$.netPrice', ROUND(o.amount_net - ol.total_price, 2),
                        '$.totalPrice', ROUND(o.amount_total - ol.total_price, 2), 
                        '$.positionPrice', ROUND(o.position_price - ol.total_price, 2)
                    )
                WHERE o.id = ?;
            ", [
                Uuid::fromHexToBytes($order->getId())
            ]);

            $this->connection->executeStatement("
                DELETE order_line_item FROM order_line_item
                INNER JOIN `order` o ON order_line_item.order_id = o.id
                WHERE 
                    o.id = ?
                    AND order_line_item.type = 'easycredit-interest';
            ", [
                Uuid::fromHexToBytes($order->getId()),
            ]);

            $this->storage->set('interest_amount', null)->persist();
            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->connection->rollBack();
        }
    }
}
