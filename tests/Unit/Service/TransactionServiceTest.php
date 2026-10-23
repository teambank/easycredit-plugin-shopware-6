<?php

declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Test\Unit\Service;

use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Service\TransactionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Teambank\EasyCreditApiV3\Model\CaptureRequest;
use Teambank\EasyCreditApiV3\Model\RefundRequest;
use Teambank\EasyCreditApiV3\Model\TransactionResponse;
use Teambank\EasyCreditApiV3\Service\TransactionApi;

class TransactionServiceTest extends TestCase
{
    public function testCaptureUsesSalesChannelCredentials(): void
    {
        $api = $this->createMock(TransactionApi::class);
        $api->expects($this->once())
            ->method('apiMerchantV3TransactionTransactionIdGet')
            ->with('ec-tx-1')
            ->willReturn($this->transactionWithStatus(TransactionResponse::STATUS_REPORT_CAPTURE));
        $api->expects($this->once())
            ->method('apiMerchantV3TransactionTransactionIdCapturePost')
            ->with('ec-tx-1', $this->isInstanceOf(CaptureRequest::class));

        $factory = $this->createMock(IntegrationFactory::class);
        $factory->expects($this->exactly(2))
            ->method('createTransactionApi')
            ->with('sales-channel-1')
            ->willReturn($api);

        $service = new TransactionService($factory, $this->createMock(LoggerInterface::class));
        $service->captureTransaction($this->orderEvent('sales-channel-1', 'ec-tx-1'));
    }

    public function testRefundUsesSalesChannelCredentials(): void
    {
        $orderDetails = new class() {
            public function getCurrentOrderValue(): float
            {
                return 199.0;
            }
        };

        $remoteTransaction = $this->createMock(TransactionResponse::class);
        $remoteTransaction->method('getOrderDetails')->willReturn($orderDetails);

        $api = $this->createMock(TransactionApi::class);
        $api->expects($this->once())
            ->method('apiMerchantV3TransactionTransactionIdGet')
            ->with('ec-tx-1')
            ->willReturn($remoteTransaction);
        $api->expects($this->once())
            ->method('apiMerchantV3TransactionTransactionIdRefundPost')
            ->with('ec-tx-1', $this->isInstanceOf(RefundRequest::class));

        $factory = $this->createMock(IntegrationFactory::class);
        $factory->expects($this->exactly(2))
            ->method('createTransactionApi')
            ->with('sales-channel-1')
            ->willReturn($api);

        $service = new TransactionService($factory, $this->createMock(LoggerInterface::class));
        $service->refundTransaction($this->orderEvent('sales-channel-1', 'ec-tx-1'));
    }

    private function transactionWithStatus(string $status): TransactionResponse
    {
        $transaction = $this->createMock(TransactionResponse::class);
        $transaction->method('getStatus')->willReturn($status);

        return $transaction;
    }

    private function orderEvent(string $salesChannelId, string $transactionId): OrderStateMachineStateChangeEvent
    {
        $orderTransaction = $this->createMock(OrderTransactionEntity::class);
        $orderTransaction->method('getCustomFields')->willReturn([
            'easycredit_transaction_id' => $transactionId,
        ]);

        $transactions = $this->createMock(OrderTransactionCollection::class);
        $transactions->method('first')->willReturn($orderTransaction);

        $order = $this->createMock(OrderEntity::class);
        $order->method('getSalesChannelId')->willReturn($salesChannelId);
        $order->method('getTransactions')->willReturn($transactions);

        $event = $this->createMock(OrderStateMachineStateChangeEvent::class);
        $event->method('getOrder')->willReturn($order);

        return $event;
    }
}
