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

class Migration1661846378BrandRelaunch extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1_661_846_378;
    }

    protected function _replace($column)
    {
        return \sprintf("%s = REPLACE(%s, 'ratenkauf by easyCredit', 'easyCredit-Ratenkauf')", $column, $column);
    }

    public function update(Connection $connection): void
    {
        $fields = [];
        foreach (['name', 'description', 'distinguishable_name'] as $column) {
            if (!$this->_columnExists($connection, 'payment_method_translation', $column)) {
                continue;
            }
            $fields[] = $this->_replace('pmt.' . $column);
        }
        $fields = \implode(', ', $fields);

        $connection->executeUpdate("
            UPDATE payment_method_translation pmt INNER JOIN payment_method pm ON pm.id = pmt.payment_method_id Set 
                {$fields}
            WHERE pm.handler_identifier = :handler;
        ", ['handler' => 'Netzkollektiv\\EasyCredit\\Payment\\Handler']);

        $connection->executeUpdate("
            UPDATE plugin_translation pt INNER JOIN plugin p ON p.id = pt.plugin_id Set
                {$this->_replace('pt.label')}
            WHERE p.name = 'EasyCreditRatenkauf';
        ");

        $connection->executeUpdate(" 
            UPDATE rule r INNER JOIN payment_method pm ON r.id = pm.availability_rule_id Set
                {$this->_replace('r.name')},
                {$this->_replace('r.description')}
            WHERE pm.handler_identifier = :handler;
        ", ['handler' => 'Netzkollektiv\\EasyCredit\\Payment\\Handler']);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    protected function _columnExists(Connection $connection, string $table, string $column): bool
    {
        $exists = $connection->fetchOne(
            'SHOW COLUMNS FROM ' . $table . ' WHERE `Field` LIKE :column',
            ['column' => $column]
        );

        return !empty($exists);
    }
}
