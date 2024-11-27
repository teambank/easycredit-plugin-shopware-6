<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Netzkollektiv\EasyCredit\Payment\Handler\InstallmentPaymentHandler;

class Migration171257360AddBillPaymentHandler extends MigrationStep
{
    const LEGACY_HANDLER_IDENTIFIER = 'Netzkollektiv\\EasyCredit\\Payment\\Handler';

    public function getCreationTimestamp(): int
    {
        return 1_712_573_360;
    }

    public function update(Connection $connection): void
    {
        $sql = "UPDATE payment_method Set handler_identifier = '" . InstallmentPaymentHandler::class . "'
            WHERE handler_identifier = '" . self::LEGACY_HANDLER_IDENTIFIER . "'";
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
