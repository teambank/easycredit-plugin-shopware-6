<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Netzkollektiv\EasyCredit\EasyCreditRatenkauf;
use Netzkollektiv\EasyCredit\Api\Storage;

class CheckoutOrderPlacedSubscriber implements EventSubscriberInterface
{
    private EntityRepository $orderTransactionRepository;

    private Storage $storage;

    public function __construct(
        EntityRepository $orderTransactionRepository,
        Storage $storage
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->storage = $storage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'addPaymentStateData',
        ];
    }

    public function addPaymentStateData(CheckoutOrderPlacedEvent $event)
    {
        $order = $event->getOrder();
        $context = $event->getContext();

        $this->persistPaymentStateData(
            $order->getTransactions()->first(),
            $context
        );
    }

    protected function persistPaymentStateData(
        OrderTransactionEntity $transaction,
        Context $context
    ): void {
        $data = [
            'id' => $transaction->getId(),
            'customFields' => [
                EasyCreditRatenkauf::ORDER_TRANSACTION_CUSTOM_FIELDS_EASYCREDIT_TRANSACTION_ID => $this->storage->get('transaction_id'),
                EasyCreditRatenkauf::ORDER_TRANSACTION_CUSTOM_FIELDS_EASYCREDIT_TECHNICAL_TRANSACTION_ID => $this->storage->get('token')
            ],
        ];
        $this->orderTransactionRepository->update([$data], $context);
    }
}
