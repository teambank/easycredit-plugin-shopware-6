<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use Teambank\RatenkaufByEasyCreditApiV3\Model\RedirectLinks;
use Teambank\RatenkaufByEasyCreditApiV3\Model\OrderDetails;
use Teambank\RatenkaufByEasyCreditApiV3\Model\CustomerRelationship;
use Netzkollektiv\EasyCredit\Helper\MetaDataProvider;
use Netzkollektiv\EasyCredit\Api\Storage;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;
use Netzkollektiv\EasyCredit\Api\Quote\AddressBuilder;
use Netzkollektiv\EasyCredit\Api\Quote\ItemBuilder;
use Netzkollektiv\EasyCredit\Api\Quote\CustomerBuilder;
use Netzkollektiv\EasyCredit\Cart\Processor;
use Netzkollektiv\EasyCredit\Service\FlexpriceService;

use Teambank\RatenkaufByEasyCreditApiV3\Integration;
use Teambank\RatenkaufByEasyCreditApiV3\Model\Transaction;
use Teambank\RatenkaufByEasyCreditApiV3\Model\ShippingAddress;
use Teambank\RatenkaufByEasyCreditApiV3\Model\InvoiceAddress;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QuoteBuilder
{
    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var SalesChannelContext
     */
    protected $salesChannelContext;

    /**
     * @var CustomerEntity
     */
    protected $customer;

    protected $settings;

    /**
     * @var Storage
     */
    protected $storage;

    protected $metaDataProvider;

    protected $router; 

    protected $flexpriceService;

    protected $addressBuilder;

    protected $itemBuilder;

    protected $customerBuilder;

    protected $systemBuilder; 

    public function __construct(
        MetaDataProvider $metaDataProvider,
        SettingsServiceInterface $settingsService,
        Storage $storage,
        UrlGeneratorInterface $router,
        FlexpriceService $flexpriceService,
        AddressBuilder $addressBuilder,
        ItemBuilder $itemBuilder,
        CustomerBuilder $customerBuilder,
        SystemBuilder $systemBuilder
    ) {
        $this->metaDataProvider = $metaDataProvider;
        $this->settings = $settingsService;
        $this->storage = $storage;
        $this->router = $router;
        $this->flexpriceService = $flexpriceService;
        $this->addressBuilder = $addressBuilder;
        $this->itemBuilder = $itemBuilder;
        $this->customerBuilder = $customerBuilder;
        $this->systemBuilder = $systemBuilder;
    }

    public function getId(): ?string
    {
        if ($this->cart instanceof Cart) {
            return $this->cart->getToken();
	}
	return null;
    }

    public function getShippingMethod(): ?string
    {
        $delivery = $this->cart->getDeliveries()->first();
        if ($delivery === null) {
            return '';
        }
        $shippingMethod = $delivery->getShippingMethod()->getName();

        if ($this->getIsClickAndCollect()) {
            $shippingMethod = '[Selbstabholung] '.$shippingMethod;
        }
        return $shippingMethod;
    }

    public function getIsClickAndCollect(): Bool {
        $delivery = $this->cart->getDeliveries()->first();
        if ($delivery === null) {
            return false;
        }
        
        return $delivery->getShippingMethod()->getId() 
            === $this->settings->getSettings($this->salesChannelContext->getSalesChannel()->getId())->getClickAndCollectShippingMethod();
    }

    public function getDuration(): ?string {
        return $this->storage->get('duration');
    }

    public function getGrandTotal(): float
    {
        return $this->cart->getPrice()->getTotalPrice();
    }

    public function getInvoiceAddress(): ?InvoiceAddress
    {
        if (!$address = $this->customer->getActiveBillingAddress()) {
            throw new QuoteInvalidException();
        }

        return $this->addressBuilder
            ->setAddress(new InvoiceAddress())
            ->build($address);
    }

    public function getShippingAddress(): ShippingAddress
    {
        $address = $this->cart->getDeliveries()->getAddresses()->first();
        if ($address === null) {
            throw new QuoteInvalidException();
        }

        return $this->addressBuilder
            ->setAddress(new ShippingAddress())
            ->build($address);
    }

    public function getCustomer()
    {
        /*if ($this->customer->getActiveBillingAddress() === null) {
            throw new QuoteInvalidException();
        }*/

        if (!$this->customer) {
            return null;
        }

        return $this->customerBuilder->build(
            $this->customer,
            $this->customer ? $this->customer->getActiveBillingAddress() : null
        );
    }

    public function getSystem() {
        return $this->systemBuilder->build();
    }

    public function getItems(): array
    {
        return $this->_getItems(
            $this->cart->getLineItems()->getElements()
        );
    }

    /**
     * @param \Shopware\Core\Checkout\Cart\LineItem\LineItem[] $items
     */
    protected function _getItems(array $items): array
    {
        $_items = [];
        foreach ($items as $item) {
            if ($item->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            $quoteItem = $this->itemBuilder->build($item, $this->salesChannelContext);
            if ($quoteItem->getPrice() <= 0) {
                continue;
            }
            $_items[] = $quoteItem;
        }

        return $_items;
    }

    protected function getRedirectLinks() {
        return new RedirectLinks([
            'urlSuccess' => $this->router->generate('frontend.easycredit.return', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'urlCancellation' => $this->router->generate('frontend.easycredit.cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'urlDenial' => $this->router->generate('frontend.easycredit.reject', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
    }

    protected function isExpress() {
        return $this->storage->get('express');
    }

    public function build($cart, SalesChannelContext $salesChannelContext): Transaction {
        $this->cart = $cart;
        $this->salesChannelContext = $salesChannelContext;
        $this->customer = $salesChannelContext->getCustomer();

        if (!$this->isExpress()) {
            if ($cart instanceof Cart && $cart->getDeliveries()->getAddresses()->first() === null) {
                throw new QuoteInvalidException();
            }
            if (!$this->customer) {
                throw new QuoteInvalidException();
            }
        }

        return new Transaction([
            'financingTerm' => $this->getDuration(),
            'orderDetails' => new OrderDetails([
                'orderValue' => $this->getGrandTotal(),
                'orderId' => $this->getId(),
                'numberOfProductsInShoppingCart' => \count($this->getItems()),
                'invoiceAddress' => $this->isExpress() ? null : $this->getInvoiceAddress(),
                'shippingAddress' => $this->isExpress() ? null : $this->getShippingAddress(),
                'shoppingCartInformation' => $this->getItems(),
                'withoutFlexprice' => $this->flexpriceService->shouldDisableFlexprice($this->salesChannelContext, $this->cart)
            ]),
            'shopsystem' => $this->getSystem(),
            'customer' => $this->getCustomer(),
            'customerRelationship' => new CustomerRelationship([
                'customerSince' => ($this->customer && $this->customer->getCreatedAt() instanceof \DateTimeImmutable) ? $this->customer->getCreatedAt()->format('Y-m-d') : null,
                'orderDoneWithLogin' => $this->customer && !$this->customer->getGuest(),
                'numberOfOrders' => ($this->customer) ? $this->customer->getOrderCount() : 0,
                'logisticsServiceProvider' => $this->getShippingMethod()      
            ]),
            'redirectLinks' => $this->getRedirectLinks()
        ]);
    }
}
