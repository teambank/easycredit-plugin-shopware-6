<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Util;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * Validates URLs used in the easyCredit checkout flow.
 *
 * - Shop return URLs (success / cancel / denial) must belong to the current sales channel.
 * - Outbound payment redirects (redirect_url from the API) must target the easyCredit domain only.
 */
class RedirectUrlValidator
{
    private const EASYCREDIT_DOMAINS = [
        'easycredit.de',
        'easycredit-ratenkauf.de',
    ];

    public static function isEasyCreditHost(?string $url): bool
    {
        $host = \parse_url((string) $url, PHP_URL_HOST);
        if ($host === null || $host === '') {
            return false;
        }

        $host = \strtolower($host);

        foreach (self::EASYCREDIT_DOMAINS as $domain) {
            if (self::isHostInDomain($host, $domain)) {
                return true;
            }
        }

        return false;
    }

    public static function isAllowedShopReturnUrl(string $url, SalesChannelContext $context): bool
    {
        if (self::isEasyCreditHost($url)) {
            return false;
        }

        $scheme = \parse_url($url, PHP_URL_SCHEME);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        $host = \parse_url($url, PHP_URL_HOST);
        if ($host === null || $host === '') {
            return false;
        }

        $domains = $context->getSalesChannel()->getDomains();
        if ($domains === null) {
            return false;
        }

        foreach ($domains as $domain) {
            if (\parse_url($domain->getUrl(), PHP_URL_HOST) === $host) {
                return true;
            }
        }

        return false;
    }

    private static function isHostInDomain(string $host, string $domain): bool
    {
        if ($host === $domain) {
            return true;
        }

        $suffix = '.' . $domain;

        return \strlen($host) > \strlen($suffix)
            && \substr($host, -\strlen($suffix)) === $suffix;
    }
}
