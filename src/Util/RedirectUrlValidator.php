<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Util;

use Shopware\Core\System\SalesChannel\SalesChannelContext;

class RedirectUrlValidator
{
    private const EASYCREDIT_HOSTS = [
        'ratenkauf.easycredit.de',
        'partner.easycredit-ratenkauf.de',
    ];

    public static function isEasyCreditHost(?string $url): bool
    {
        $host = \parse_url((string) $url, PHP_URL_HOST);

        return $host !== null && \in_array($host, self::EASYCREDIT_HOSTS, true);
    }

    public static function isAllowedShopReturnUrl(string $url, SalesChannelContext $context): bool
    {
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
}
