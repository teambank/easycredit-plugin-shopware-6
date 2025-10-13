<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Content\Category\Event;

use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class CategoryCmsPageLoadedEvent
{
    private SalesChannelContext $salesChannelContext;

    private CmsPageEntity $page;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        CmsPageEntity $page
    ) {
        $this->salesChannelContext = $salesChannelContext;
        $this->page = $page;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getPage(): CmsPageEntity
    {
        return $this->page;
    }
}
