<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1758107903WidgetCompatibility extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1758107903;
    }

    public function update(Connection $connection): void
    {
        // use :first-of-type to select only the first one
        $connection->executeStatement("
            UPDATE system_config Set configuration_value = JSON_SET(configuration_value, '$._value', '.checkout-aside-action:not(.d-grid):first-of-type') WHERE 
                configuration_key = 'EasyCreditRatenkauf.config.widgetSelectorCart' AND
                (JSON_EXTRACT(configuration_value, '$._value') = '.checkout-aside-action' OR JSON_EXTRACT(configuration_value, '$._value') = '.checkout-aside-action:not(.d-grid)');
        ");
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
