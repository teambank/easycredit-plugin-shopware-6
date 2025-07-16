<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment\State;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Doctrine\DBAL\Connection;

class PaymentStateService
{
    private Connection $connection;

    private EntityRepository $paymentStateRepository;

    public function __construct(
        Connection $connection,
        EntityRepository $paymentStateRepository
    ) {
        $this->connection = $connection;
        $this->paymentStateRepository = $paymentStateRepository;
    }

    public function load($salesChannelContext)
    {
        if (empty($salesChannelContext->getToken())) {
            return; // skip silently, happens if a salesChannelContext is created without token
        }

        $criteria = new Criteria([$salesChannelContext->getToken()]);

        return $this->paymentStateRepository
            ->search($criteria, $salesChannelContext->getContext())
            ->first();
    }

    public function save($salesChannelContext, $data)
    {
        if ($data) {
            $sql = <<<'SQL'
            INSERT INTO `easycredit_payment_state` (`token`, `payload`)
            VALUES (:token, :payload)
            ON DUPLICATE KEY UPDATE `payload` = :payload;
            SQL;

            $data = [
                'token' => $salesChannelContext->getToken(),
                'payload' => \json_encode($data)
            ];
        } else {
            $sql = <<<'SQL'
            DELETE FROM `easycredit_payment_state` WHERE token = :token;
            SQL;

            $data = [
                'token' => $salesChannelContext->getToken()
            ];
        }

        $query = new RetryableQuery($this->connection, $this->connection->prepare($sql));
        $query->execute($data);
    }
}
