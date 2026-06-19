<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Test\Unit\Setting;

use Netzkollektiv\EasyCredit\Setting\Exception\SettingsInvalidException;
use Netzkollektiv\EasyCredit\Setting\SettingStruct;
use Netzkollektiv\EasyCredit\Setting\SettingStructValidator;
use PHPUnit\Framework\TestCase;

class SettingStructValidatorTest extends TestCase
{
    public function testValidateAcceptsValidSettings(): void
    {
        $settings = new SettingStruct();
        $settings->setWebshopId('webshop-id');
        $settings->setApiPassword('api-password');

        SettingStructValidator::validate($settings);

        self::assertTrue(true);
    }

    public function testValidateThrowsForInvalidWebshopId(): void
    {
        $settings = $this->createMock(SettingStruct::class);
        $settings->method('getWebshopId')->willThrowException(new \TypeError('Invalid webshopId'));

        $this->expectException(SettingsInvalidException::class);
        $this->expectExceptionMessage('webshopId');

        SettingStructValidator::validate($settings);
    }

    public function testValidateThrowsForInvalidApiPassword(): void
    {
        $settings = $this->createMock(SettingStruct::class);
        $settings->method('getWebshopId')->willReturn('webshop-id');
        $settings->method('getApiPassword')->willThrowException(new \TypeError('Invalid apiPassword'));

        $this->expectException(SettingsInvalidException::class);
        $this->expectExceptionMessage('apiPassword');

        SettingStructValidator::validate($settings);
    }
}
