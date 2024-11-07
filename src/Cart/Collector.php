<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Cart;

use Netzkollektiv\EasyCredit\Api\Storage;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\Exception\InvalidPayloadException;
use Shopware\Core\Checkout\Cart\Exception\InvalidQuantityException;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Collector implements CartDataCollectorInterface
{
    protected $storage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        Storage $storage,
        TranslatorInterface $translator,
        RequestStack $requestStack
    ) {
        $this->storage = $storage;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    /**
     * @throws InvalidPayloadException
     * @throws InvalidQuantityException
     */
    public function collect(CartDataCollection $data, Cart $cart, SalesChannelContext $context, CartBehavior $behavior): void
    {
        if (!$this->requestStack->getCurrentRequest()) {
            return; // do not run in CLI
        }
        if ($cart->getToken() !== $this->storage->get('cartToken')) {
            return;
        }
        if (!$price = $this->getInterestPrice()) {
            return;
        }
        $data->set(Processor::DATA_KEY, new LineItemCollection([
            $this->buildInterestLineItem($price),
        ]));
    }

    public function getInterestPrice(): ?CalculatedPrice
    {
        if ($this->storage->get('interest_amount') === null) {
            return null;
        }
        $this->storage->set('debug','adding interest line item: '.(float)$this->storage->get('interest_amount'));

        return new CalculatedPrice(
            (float) $this->storage->get('interest_amount'),
            (float) $this->storage->get('interest_amount'),
            new CalculatedTaxCollection([new CalculatedTax(0, 0, 0)]),
            new TaxRuleCollection([new TaxRule(0)])
        );
    }

    protected function buildInterestLineItem(CalculatedPrice $price): LineItem
    {
        $id = 'easycredit-interest';

        $interestItem = new LineItem($id, Processor::LINE_ITEM_TYPE);
        $interestItem->setLabel($this->translator->trans('easycredit.interest-line-item'));
        $interestItem->setDescription($this->translator->trans('easycredit.interest-line-item'));
        $interestItem->setGood(false);
        $interestItem->setRemovable(false);
        $interestItem->setPayloadValue('productNumber', '');
        $interestItem->setDeliveryInformation(
            new DeliveryInformation(
                1, // $stock
                0, // $weight
                true // $freeDelivery
            )
        );
        $interestItem->setPrice($price);
        $interestItem->setReferencedId($id);

        return $interestItem;
    }
}
