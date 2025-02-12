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

class Migration1715692138MigrateWidgetSettings extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1715692138;
    }

    public function update(Connection $connection): void
    {
        // document.querySelectorAll in new widget implementation matches multiple elements
        // => change default selector to something more specific in order two show widget only once
        $connection->executeStatement("
            UPDATE system_config Set configuration_value = JSON_SET(configuration_value, '$._value', '.checkout-aside-action:not(.d-grid)') WHERE 
                configuration_key = 'EasyCreditRatenkauf.config.widgetSelectorCart' AND
                JSON_EXTRACT(configuration_value, '$._value') = '.checkout-aside-action';
        ");
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
