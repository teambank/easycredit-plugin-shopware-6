<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Test\Helper;

use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

final class SalesChannelContextFactory
{
    /**
     * @param list<string> $domainUrls
     */
    public static function createWithDomains(array $domainUrls): SalesChannelContext
    {
        $domains = new SalesChannelDomainCollection();

        foreach ($domainUrls as $domainUrl) {
            $domain = new SalesChannelDomainEntity();
            $domain->setUniqueIdentifier(Uuid::randomHex());
            $domain->setUrl($domainUrl);
            $domains->add($domain);
        }

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setUniqueIdentifier(Uuid::randomHex());
        $salesChannel->setDomains($domains);

        $context = MockFactory::create(SalesChannelContext::class);
        $context->method('getSalesChannel')->willReturn($salesChannel);

        return $context;
    }
}
