<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api\Quote;

use Teambank\EasyCreditApiV3\Model\ArticleNumberItem;
use Netzkollektiv\EasyCredit\Helper\MetaDataProvider;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Teambank\EasyCreditApiV3\Integration;
use Teambank\EasyCreditApiV3\Model\ShoppingCartInformationItem;

class ItemBuilder
{
    /**
     * @var LineItem
     */
    protected $item;

    protected $context;

    protected $metaDataProvider;

    protected $seoUrlReplacer;

    public function __construct(
        MetaDataProvider $metaDataProvider,
        SeoUrlPlaceholderHandlerInterface $seoUrlReplacer
    ) {
        $this->metaDataProvider = $metaDataProvider;
        $this->seoUrlReplacer = $seoUrlReplacer;
    }

    public function getManufacturer(): string
    {
        if (\method_exists($this->item, 'hasPayloadValue')
            && $this->item->hasPayloadValue('manufacturerId')
        ) {
            $manufacturer = $this->metaDataProvider->getManufacturer(
                $this->item->getPayloadValue('manufacturerId'),
                $this->context
            );
            if (isset($manufacturer->getTranslated()['name'])) {
                return $manufacturer->getTranslated()['name'];
            }
        }

        return '';
    }

    public function getCategory(): string
    {
        $categoryNames = [];

        if (\method_exists($this->item, 'hasPayloadValue') &&
	    $this->item->hasPayloadValue('categoryIds') &&
	    \is_array($this->item->getPayloadValue('categoryIds')) &&
	    !empty(\array_filter($this->item->getPayloadValue('categoryIds')))
        ) {
            $categories = $this->metaDataProvider->getCategories(
                $this->item->getPayloadValue('categoryIds'),
                $this->context
            );
            foreach ($categories as $category) {
                if ($category->getTranslated()['name']) {
                    $categoryNames[] = $category->getTranslated()['name'];
                }
            }
        }

        return \mb_substr(\implode(', ', $categoryNames), 0, 255);
    }

    protected function getSkus(): array
    {
        if (!\method_exists($this->item,'getPayloadValue')) {
            return [];
        }
        
        $skus = [];
        foreach (\array_filter([
            'shopware-id' => $this->item->getPayloadValue('productNumber'),
        ]) as $type => $sku) {
            $skus[] = new ArticleNumberItem([
                'numberType' => $type,
                'number' => $sku
            ]);
        }
        return $skus;
    }

    public function build($item, SalesChannelContext $context) {
        $this->item = $item;
        $this->context = $context;

        return new ShoppingCartInformationItem([
            'productName' => $item->getLabel(),
            'productUrl' => $item->getReferencedId() ? $this->seoUrlReplacer->replace(
                $this->seoUrlReplacer->generate('frontend.detail.page', ['productId' => $item->getReferencedId()]),
                $context->getSalesChannel()->getDomains()->first()->getUrl(),
                $context
            ) : null,
            'productImageUrl' => $item->getCover() ? $item->getCover()->getUrl() : null,
            'quantity' => $item->getQuantity(),
            'price' => $item->getPrice() === null ? 0 : $item->getPrice()->getTotalPrice(),
            'manufacturer' => $this->getManufacturer(),
            'productCategory' => $this->getCategory(),
            'articleNumber' => $this->getSkus()
        ]);
    }
}
