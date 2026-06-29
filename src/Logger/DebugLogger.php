<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Logger;

use Psr\Log\LoggerInterface;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;

class DebugLogger
{
    private LoggerInterface $logger;

    private SettingsServiceInterface $settings;

    public function __construct(
        LoggerInterface $logger,
        SettingsServiceInterface $settings
    ) {
        $this->logger = $logger;
        $this->settings = $settings;
    }

    public function debug(string $message, ?string $salesChannelId = null, array $context = []): void
    {
        if (!$this->isEnabled($salesChannelId)) {
            return;
        }

        $this->logger->debug($message, $context);
    }

    public function isEnabled(?string $salesChannelId = null): bool
    {
        try {
            return $this->settings->getSettings($salesChannelId, false)->getDebug();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
