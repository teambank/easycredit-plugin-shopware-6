<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Util\Migrator;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Netzkollektiv\EasyCredit\Migration\Migration171257360AddBillPaymentHandler;
use Netzkollektiv\EasyCredit\Payment\Handler\BillPaymentHandler;
use Netzkollektiv\EasyCredit\Payment\Handler\InstallmentPaymentHandler;
use Shopware\Core\Checkout\Cart\Rule\LineItemProductTypeRule;
use Shopware\Core\Framework\Migration\IndexerQueuer;

/**
 * Migrates easyCredit availability rules from the deprecated Shopware rule condition
 * "Item with product state" (cartLineItemProductStates / LineItemProductStatesRule) to
 * "Item with product type" (cartLineItemProductType / LineItemProductTypeRule).
 *
 * Shopware deprecated product states in 6.7 in favour of product types; the old condition
 * will be removed in 6.8. Our plugin used to create availability rules with the legacy
 * condition on install (and still does on Shopware < 6.7 where the new rule does not exist).
 *
 * This migrator is invoked from plugin update and activation (see EasyCreditRatenkauf) rather
 * than a one-shot MigrationStep, because Shopware migrations run only once: if the plugin was
 * updated while still on Shopware < 6.7, a migration would no-op and be marked as executed,
 * and would never run again after the shop is upgraded to 6.7+. Running here is idempotent
 * and deferred until LineItemProductTypeRule is actually available.
 */
class ProductStatesRuleMigrator
{
    public function migrate(Connection $connection): bool
    {
        // New rule type exists only from Shopware 6.7 onward; older cores keep the legacy condition.
        if (!\class_exists(LineItemProductTypeRule::class)) {
            return false;
        }

        $handlerIdentifiers = [
            InstallmentPaymentHandler::class,
            BillPaymentHandler::class,
            Migration171257360AddBillPaymentHandler::LEGACY_HANDLER_IDENTIFIER,
        ];

        $ruleIds = $connection->fetchFirstColumn(
            'SELECT DISTINCT rc.rule_id
             FROM rule_condition rc
             INNER JOIN payment_method pm ON pm.availability_rule_id = rc.rule_id
             WHERE rc.type = :ruleType
               AND pm.handler_identifier IN (:handlers)',
            [
                'ruleType' => 'cartLineItemProductStates',
                'handlers' => $handlerIdentifiers,
            ],
            [
                'handlers' => ArrayParameterType::STRING,
            ]
        );

        if ($ruleIds === []) {
            return false;
        }

        // Map legacy productState values (is-physical / is-download) to productType (physical / digital).
        $connection->executeStatement(
            'UPDATE rule_condition rc
             INNER JOIN payment_method pm ON pm.availability_rule_id = rc.rule_id
             SET rc.type = :newRuleType,
                 rc.value = CASE JSON_UNQUOTE(JSON_EXTRACT(rc.value, \'$.productState\'))
                     WHEN \'is-physical\' THEN JSON_OBJECT(
                         \'operator\', JSON_UNQUOTE(JSON_EXTRACT(rc.value, \'$.operator\')),
                         \'productType\', \'physical\'
                     )
                     WHEN \'is-download\' THEN JSON_OBJECT(
                         \'operator\', JSON_UNQUOTE(JSON_EXTRACT(rc.value, \'$.operator\')),
                         \'productType\', \'digital\'
                     )
                     ELSE rc.value
                 END
             WHERE rc.type = :oldRuleType
               AND pm.handler_identifier IN (:handlers)',
            [
                'newRuleType' => 'cartLineItemProductType',
                'oldRuleType' => 'cartLineItemProductStates',
                'handlers' => $handlerIdentifiers,
            ],
            [
                'handlers' => ArrayParameterType::STRING,
            ]
        );

        // Force rule payload rebuild so the admin and rule matcher see the updated conditions.
        $connection->executeStatement(
            'UPDATE rule SET payload = NULL WHERE id IN (:rule_ids)',
            ['rule_ids' => $ruleIds],
            ['rule_ids' => ArrayParameterType::BINARY]
        );

        IndexerQueuer::registerIndexer($connection, 'rule.indexer');

        return true;
    }
}
