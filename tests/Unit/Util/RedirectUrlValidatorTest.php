<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Test\Unit\Util;

use Netzkollektiv\EasyCredit\Test\Helper\SalesChannelContextFactory;
use Netzkollektiv\EasyCredit\Util\RedirectUrlValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RedirectUrlValidatorTest extends TestCase
{
    #[DataProvider('easyCreditHostProvider')]
    public function testIsEasyCreditHostAcceptsAllowedHosts(string $url): void
    {
        self::assertTrue(RedirectUrlValidator::isEasyCreditHost($url));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function easyCreditHostProvider(): iterable
    {
        yield 'ratenkauf host' => ['https://ratenkauf.easycredit.de/payment'];
        yield 'partner host' => ['https://partner.easycredit-ratenkauf.de/return'];
    }

    #[DataProvider('disallowedHostProvider')]
    public function testIsEasyCreditHostRejectsOtherHosts(?string $url): void
    {
        self::assertFalse(RedirectUrlValidator::isEasyCreditHost($url));
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function disallowedHostProvider(): iterable
    {
        yield 'shop host' => ['https://shop.example.com/checkout'];
        yield 'empty url' => [''];
        yield 'null url' => [null];
        yield 'invalid url' => ['not-a-url'];
    }

    public function testIsAllowedShopReturnUrlAcceptsConfiguredDomain(): void
    {
        $context = SalesChannelContextFactory::createWithDomains([
            'https://shop.example.com',
            'https://www.shop.example.com/de',
        ]);

        self::assertTrue(
            RedirectUrlValidator::isAllowedShopReturnUrl('https://shop.example.com/checkout/finish', $context)
        );
    }

    public function testIsAllowedShopReturnUrlRejectsUnknownHost(): void
    {
        $context = SalesChannelContextFactory::createWithDomains([
            'https://shop.example.com',
        ]);

        self::assertFalse(
            RedirectUrlValidator::isAllowedShopReturnUrl('https://evil.example.com/checkout/finish', $context)
        );
    }

    public function testIsAllowedShopReturnUrlRejectsInvalidUrl(): void
    {
        $context = SalesChannelContextFactory::createWithDomains([
            'https://shop.example.com',
        ]);

        self::assertFalse(RedirectUrlValidator::isAllowedShopReturnUrl('not-a-url', $context));
    }

    public function testIsAllowedShopReturnUrlRejectsWhenNoDomainsConfigured(): void
    {
        $context = SalesChannelContextFactory::createWithDomains([]);

        self::assertFalse(
            RedirectUrlValidator::isAllowedShopReturnUrl('https://shop.example.com/checkout/finish', $context)
        );
    }
}
