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
use Shopware\Core\Framework\Uuid\Uuid;
use Netzkollektiv\EasyCredit\Payment\Handler\InstallmentPaymentHandler;
use Netzkollektiv\EasyCredit\Payment\Handler\BillPaymentHandler;
use Shopware\Core\Defaults;

class Migration1734687546AdaptPaymentRules extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1657600000; // Timestamp for your migration
    }

    /**
     * This method will be called when the migration is executed.
     */
    public function update(Connection $connection): void
    {
        // Fetch the payment methods with the specific handler
        $sql = "SELECT id, availability_rule_id
                FROM `payment_method`
                WHERE `handler_identifier` IN ('" . \addslashes(InstallmentPaymentHandler::class) . "', '" . \addslashes(BillPaymentHandler::class) . "')";

        $paymentMethods = $connection->fetchAllAssociative($sql);

        foreach ($paymentMethods as $paymentMethod) {
            $ruleId = $paymentMethod['availability_rule_id'];

            $sql = "SELECT id, rule_id, type, parent_id
                    FROM `rule_condition`
                    WHERE `rule_id` = :ruleId";

            $ruleConditions = $connection->fetchAllAssociative($sql, ['ruleId' => $ruleId]);

            if ($billingCountryCondition = $this->getBillingCountryCondition($ruleConditions)) {
                // Create the orContainer condition
                $containerId = $this->insertCondition($connection, $ruleId, [
                    'parent_id' => $billingCountryCondition['parent_id'],  // Keep the parent of the original condition
                    'type' => 'orContainer'
                ]);

                // Create the new "empty" condition to add to the rule
                $this->insertCondition($connection, $ruleId, [
                    'parent_id' => $containerId,
                    'type' => $billingCountryCondition['type'],
                    'value' => \json_encode(['operator' => 'empty'])
                ]);

                // Update the parent_id of the existing condition, making it children of the new "orContainer"
                $connection->update('rule_condition', [
                    'parent_id' => $containerId
                ], [
                    'id' => $billingCountryCondition['id']
                ]);
            }

            if (!\class_exists(\Shopware\Core\Checkout\Cart\Rule\LineItemProductStatesRule::class)) {
                return; // this applies to Shopware 6.4.18 or earlier; conditions must be added manually after update to 6.4.19 or higher
            }

            if ($baseCondition = $this->getBaseCondition($ruleConditions)) {

                $containerId = $this->insertCondition($connection, $ruleId, [
                    'parent_id' => $baseCondition['id'],  // Keep the parent of the original condition
                    'type' => 'orContainer'
                ]);

                $matchAllLineItemsId = $this->insertCondition($connection, $ruleId, [
                    'parent_id' => $containerId, // Parent is the "orContainer"
                    'type' => 'allLineItemsContainer',
                    'value' => '{"type": "product"}'
                ]);

                // Create the "lineItemGoodsTotal" condition (child of "orContainer")
                $this->insertCondition($connection, $ruleId, [
                    'parent_id' => $matchAllLineItemsId,
                    'type' => 'cartLineItemProductStates',
                    'value' => \json_encode([
                        'operator' => '=',
                        "productState" => "is-physical"
                    ])
                ]);

                // Create the "lineItemGoodsTotal" condition (child of "orContainer")
                $this->insertCondition($connection, $ruleId, [
                    'parent_id' => $containerId,
                    'type' => 'cartLineItemGoodsTotal',
                    'value' => \json_encode([
                        'operator' => '=',
                        'count' => 0,
                    ])
                ]);
            }
        }
    }

    protected function insertCondition($connection, $ruleId, $data)
    {
        $id = Uuid::randomBytes();
        $connection->insert('rule_condition', \array_merge([
            'id' => $id,
            'rule_id' => $ruleId,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'value' => null
        ], $data));
        return $id;
    }

    protected function getBaseCondition($ruleConditions)
    {
        foreach ($ruleConditions as $ruleCondition) {
            if ($ruleCondition['parent_id'] === null) {
                return $ruleCondition;
            }
        }
    }

    protected function getBillingCountryCondition($ruleConditions)
    {
        foreach ($ruleConditions as $ruleCondition) {
            if ($ruleCondition['type'] === 'customerBillingCountry') {
                return $ruleCondition;
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
