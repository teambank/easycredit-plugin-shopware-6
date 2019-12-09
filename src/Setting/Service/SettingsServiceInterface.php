<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Setting\Service;

use Netzkollektiv\EasyCredit\Setting\SettingStruct;

interface SettingsServiceInterface
{
    public function getSettings(?string $salesChannelId = null): SettingStruct;

    public function updateSettings(array $settings, ?string $salesChannelId = null): void;
}
