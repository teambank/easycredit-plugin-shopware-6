<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment\Handler;

use Monolog\Logger;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\EasyCreditRatenkauf;
use Netzkollektiv\EasyCredit\Payment\StateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Teambank\EasyCreditApiV3 as ApiV3;

abstract class AbstractSynchronousHandler implements SynchronousPaymentHandlerInterface
{
    protected Storage $storage;

    private EntityRepository $orderTransactionRepository;

    private StateHandler $stateHandler;

    private IntegrationFactory $integrationFactory;

    private Logger $logger;

    public function __construct(
        EntityRepository $orderTransactionRepository,
        StateHandler $stateHandler,
        IntegrationFactory $integrationFactory,
        Storage $storage,
        Logger $logger
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->stateHandler = $stateHandler;

        $this->integrationFactory = $integrationFactory;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        try {
            $checkout = $this->integrationFactory->createCheckout(
                $salesChannelContext->getSalesChannel()->getId()
            );

            $order = $transaction->getOrder();
            $orderTransaction = $transaction->getOrderTransaction();
            $orderTransaction->setOrder($order); // to access the salesChannelId in handleTransactionState

            $token = $orderTransaction->getCustomFields()[EasyCreditRatenkauf::ORDER_TRANSACTION_CUSTOM_FIELDS_EASYCREDIT_TECHNICAL_TRANSACTION_ID];
            $this->storage->set('token', $token);

            $tx = $checkout->loadTransaction();

            if (!$checkout->isApproved()) {
                $this->handlePaymentException(
                    $transaction,
                    'Transaction not valid for capture'
                );
            }

            if (!$checkout->authorize($order->getOrderNumber())) {
                $this->handlePaymentException(
                    $transaction,
                    'Transaction could not be captured'
                );
            }

            $tx = $checkout->loadTransaction();

            if ($tx->getStatus() === ApiV3\Model\TransactionInformation::STATUS_AUTHORIZED) {
                $this->stateHandler->handleTransactionState(
                    $orderTransaction,
                    $salesChannelContext->getContext()
                );
                $this->stateHandler->handleOrderState(
                    $order,
                    $salesChannelContext->getContext()
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->handlePaymentException(
                $transaction,
                'Could not complete transaction: ' . $e->getMessage()
            );
        }
    }

    abstract public function getPaymentType();

    protected function handlePaymentException($transaction, $message): void
    {
        // SW > 6.6
        if (\class_exists(PaymentException::class)) {
            throw PaymentException::syncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                $message
            );
        }
        // SW < 6.6
        if (\class_exists(SyncPaymentProcessException::class)) {
            throw new SyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $message
            );
        }
    }
}
