<?php

declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Test\Unit\Api;

use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;
use Netzkollektiv\EasyCredit\Setting\SettingStruct;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class IntegrationFactoryTest extends TestCase
{
    public function testCreateTransactionApiLoadsSalesChannelCredentials(): void
    {
        $settings = new SettingStruct();
        $settings->assign([
            'webshopId' => '1.de.channel.1',
            'apiPassword' => 'secret',
            'apiSignature' => 'signature',
            'debug' => false,
        ]);

        $settingsService = $this->createMock(SettingsServiceInterface::class);
        $settingsService->expects($this->atLeastOnce())
            ->method('getSettings')
            ->with('sales-channel-1', $this->anything())
            ->willReturn($settings);

        $factory = new IntegrationFactory(
            $settingsService,
            new NullLogger(),
            $this->createMock(Storage::class)
        );

        $factory->createTransactionApi('sales-channel-1');
    }
}
