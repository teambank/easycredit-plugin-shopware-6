<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Service;

use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Teambank\EasyCreditApiV3\Model\CaptureRequest;
use Teambank\EasyCreditApiV3\Model\RefundRequest;
use Teambank\EasyCreditApiV3\Model\TransactionResponse;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;

class TransactionService {

    private IntegrationFactory $integrationFactory;

    private $logger;

    public function __construct(
        IntegrationFactory $integrationFactory,
        LoggerInterface $logger
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->logger = $logger;
    }

    public function captureTransaction(OrderStateMachineStateChangeEvent $event): void
    {
        if (!$txId = $this->getTransactionId($event)) {
            return;
        }

        try {
            $transaction = $this->integrationFactory
                ->createTransactionApi()
                ->apiMerchantV3TransactionTransactionIdGet($txId);

            if ($transaction->getStatus() !== TransactionResponse::STATUS_REPORT_CAPTURE) {
                return;
            }

            $this->integrationFactory
                ->createTransactionApi()
                ->apiMerchantV3TransactionTransactionIdCapturePost(
                    $txId,
                    new CaptureRequest(['trackingNumber' => ''])
                );
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function refundTransaction(OrderStateMachineStateChangeEvent $event): void
    {
        if (!$txId = $this->getTransactionId($event)) {
            return;
        }

        try {
            $transaction = $this->integrationFactory
                ->createTransactionApi()
                ->apiMerchantV3TransactionTransactionIdGet($txId);

            $this->integrationFactory
                ->createTransactionApi()
                ->apiMerchantV3TransactionTransactionIdRefundPost(
                    $txId,
                    new RefundRequest(['value' => $transaction->getOrderDetails()->getCurrentOrderValue()])
                );
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }

    protected function getTransactionId(OrderStateMachineStateChangeEvent $event)
    {
        $tx = $event->getOrder()->getTransactions()->first();

        if (!$tx || !isset($tx->getCustomFields()['easycredit_transaction_id'])) {
            return false;
        }

        return $tx->getCustomFields()['easycredit_transaction_id'];
    }
}
