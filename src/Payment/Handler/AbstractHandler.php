<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment\Handler;

use Monolog\Logger;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Struct\Struct;

use Teambank\EasyCreditApiV3 as ApiV3;

use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\EasyCreditRatenkauf;
use Netzkollektiv\EasyCredit\Payment\StateHandler;

abstract class AbstractHandler extends AbstractPaymentHandler
{
    private EntityRepository $orderTransactionRepository;

    private StateHandler $stateHandler;

    private IntegrationFactory $integrationFactory;

    private Logger $logger;

    protected Storage $storage;

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

    public function supports(
        PaymentHandlerType $type,
        string $paymentMethodId,
        Context $context
    ): bool {
        return true;
    }

    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct
    ): ?RedirectResponse {
        try {

            [$orderTransaction, $order] = $this->fetchOrderTransaction($transaction->getOrderTransactionId(), $context);
            
            $checkout = $this->integrationFactory->createCheckout(
                $order->getSalesChannelId()
            );

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
                    $context
                );
                $this->stateHandler->handleOrderState(
                    $order,
                    $context
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->handlePaymentException(
                $transaction,
                'Could not complete transaction: ' . $e->getMessage()
            );
        }
        return null;
    }

    protected function handlePaymentException($transaction, $message)
    {
        throw PaymentException::syncProcessInterrupted(
            $transaction->getOrderTransaction()->getId(),
            $message
        );
    }

    abstract public function getPaymentType();

    /**
     * @return array{0: OrderTransactionEntity, 1: OrderEntity}
    */
    private function fetchOrderTransaction(string $transactionId, Context $context): array
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order.billingAddress.country');
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('order.lineItems');
        $criteria->addAssociation('order.orderCustomer.customer');
        $criteria->addAssociation('order.salesChannel');

        $transaction = $this->orderTransactionRepository->search($criteria, $context)->first();
        \assert($transaction instanceof OrderTransactionEntity);

        $order = $transaction->getOrder();
        \assert($order instanceof OrderEntity);

        return [$transaction, $order];
    }
}
