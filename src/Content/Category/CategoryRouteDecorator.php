<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Content\Category;

use Netzkollektiv\EasyCredit\Content\Category\Event\CategoryCmsPageLoadedEvent;
use Shopware\Core\Content\Category\SalesChannel\AbstractCategoryRoute;
use Shopware\Core\Content\Category\SalesChannel\CategoryRouteResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CategoryRouteDecorator extends AbstractCategoryRoute
{
    private AbstractCategoryRoute $decorated; 

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        AbstractCategoryRoute $decorated,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->decorated = $decorated;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getDecorated(): AbstractCategoryRoute
    {
        return $this->decorated;
    }

    public function load(string $navigationId, Request $request, SalesChannelContext $context): CategoryRouteResponse
    {
        $response = $this->decorated->load($navigationId, $request, $context);

        $cmsPage = $response->getCategory()->getCmsPage();
        if ($cmsPage !== null) {
            $this->eventDispatcher->dispatch(
                new CategoryCmsPageLoadedEvent($context, $cmsPage)
            );
        }

        return $response;
    }
}
