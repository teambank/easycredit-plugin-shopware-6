<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1603803110UpdateSettings extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_603_803_110;
    }

    public function update(Connection $connection): void
    {
        $sql = "UPDATE system_config Set configuration_key = REPLACE(configuration_key, '.settings.', '.config.') WHERE configuration_key LIKE 'EasyCreditRatenkauf.settings%'";
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
