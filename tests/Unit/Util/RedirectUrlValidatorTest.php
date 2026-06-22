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
        yield 'partner ratenkauf host' => ['https://partner.easycredit-ratenkauf.de/return'];
        yield 'partner easycredit host' => ['https://partner.easycredit.de/kontakt/'];
        yield 'www easycredit-ratenkauf host' => ['https://www.easycredit-ratenkauf.de/marketing'];
        yield 'apex easycredit host' => ['https://easycredit.de/ratenkauf'];
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
        yield 'fake easycredit suffix' => ['https://easycredit.de.evil.com/checkout'];
        yield 'fake ratenkauf suffix' => ['https://easycredit-ratenkauf.de.phishing.test/'];
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

    public function testIsAllowedShopReturnUrlRejectsEasyCreditHost(): void
    {
        $context = SalesChannelContextFactory::createWithDomains([
            'https://shop.example.com',
        ]);

        self::assertFalse(
            RedirectUrlValidator::isAllowedShopReturnUrl('https://partner.easycredit-ratenkauf.de/return', $context)
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

    public function testIsAllowedShopReturnUrlRejectsNonHttpScheme(): void
    {
        $context = SalesChannelContextFactory::createWithDomains([
            'https://shop.example.com',
        ]);

        self::assertFalse(
            RedirectUrlValidator::isAllowedShopReturnUrl('javascript:alert(1)', $context)
        );
    }

    public function testIsAllowedShopReturnUrlRejectsWhenNoDomainsConfigured(): void
    {
        $context = SalesChannelContextFactory::createWithDomains([]);

        self::assertFalse(
            RedirectUrlValidator::isAllowedShopReturnUrl('https://shop.example.com/checkout/finish', $context)
        );
    }
}
