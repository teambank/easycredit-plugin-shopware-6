<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\Flow\FlowDefinition;
use Shopware\Core\Content\Flow\Aggregate\FlowSequence\FlowSequenceDefinition;
use Shopware\Core\Defaults;

class Migration1693824921AddDefaultFlowActions extends MigrationStep
{
    const EVENTS = [
        [
            'name' => 'easyCredit: Capture Transaction',
            'event_name' => 'state_enter.order_delivery.state.shipped',
            'action_name' => 'action.easycredit.capture',
            'legacy_setting' => 'markShipped'
        ],[
            'name' => 'easyCredit: Refund Transaction',
            'event_name' => 'state_enter.order_delivery.state.returned',
            'action_name' => 'action.easycredit.refund',
            'legacy_setting' => 'markRefunded'
        ]
    ];

    public function getCreationTimestamp(): int
    {
        return 1693824921;
    }

    public function update(Connection $connection): void
    {
        if (!\class_exists(FlowDefinition::class)) {
            return;
        }
        if (!\class_exists(FlowSequenceDefinition::class)) {
            return;
        }

        foreach (self::EVENTS as $event) {
            $flow = [
                'id' => Uuid::randomBytes(),
                'name' => $event['name'],
                'event_name' => $event['event_name'],
                'priority' => 0,
                'active' => (int) $this->getLegacySetting($connection, $event['legacy_setting']),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
            ];
            $connection->insert(FlowDefinition::ENTITY_NAME, $flow);

            $flowSequence = [
                'action_name' => $event['action_name'],
                'display_group' => 1,
                'id' => Uuid::randomBytes(),
                'position' => 1,
                'flow_id' => $flow['id'],
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
            ];
            $connection->insert(FlowSequenceDefinition::ENTITY_NAME, $flowSequence);
            $this->registerIndexer($connection, 'flow.indexer');
            $this->removeLegacySetting($connection, $event['legacy_setting']);
        }
    }

    public function removeLegacySetting($connection, $key) {
        $connection->executeStatement(
            'DELETE FROM system_config WHERE configuration_key = :key;',
            ['key' => 'EasyCreditRatenkauf.config.'. $key]
        );
    }

    public function getLegacySetting($connection, $key) {
        $settingsValue = $connection->fetchOne(
            'SELECT configuration_value FROM system_config WHERE configuration_key = :key;',
            ['key' => 'EasyCreditRatenkauf.config.'. $key]
        );

        if (!\is_string($settingsValue)) {
            return true;
        }

        try {
            $settingsValue = \json_decode($settingsValue, false, 512, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $exception) {
            return true;
        }
        return $settingsValue->_value;
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
