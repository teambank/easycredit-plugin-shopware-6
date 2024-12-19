<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Service;

use Shopware\Core\Framework\Rule\Collector\RuleConditionRegistry;
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Checkout\Cart\Rule\CartRuleScope;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;

use Shopware\Core\Checkout\Cart\SalesChannel\CartResponse;
use Shopware\Core\Checkout\Cart\CartCalculator;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;

class RuleEvaluator {

    private EntityRepository $ruleRepository;

    private SettingsServiceInterface $settingsService;

    private CartCalculator $cartCalculator;

    private Line
    private ?Cart $cart = null;

    public function __construct(
        EntityRepository $ruleRepository,
        SettingsServiceInterface $settingsService,
        CartCalculator $cartCalculator
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->settingsService = $settingsService;
        $this->cartCalculator = $cartCalculator;
    }

    public function evaluateRule($rule, Cart $cart, SalesChannelContext $salesChannelContext): bool
    {
        $scope = new CartRuleScope($cart, $salesChannelContext);
        return $rule->getPayload()->match($scope);
    }

    private function getBaseCart () {
        if (!$this->cart) {
            $reflection = new \ReflectionClass(Cart::class);
            $token = 'easycredit-'.Uuid::randomHex();

            $this->cart = $reflection->getConstructor()->getNumberOfParameters() == 2 ?
                new Cart($token, $token):
                new Cart($token);
        }
        return $this->cart;
    }

    public function getCartForProduct ($product, SalesChannelContext $salesChannelContext, $quantity = 1) {
        $cart = $this->getBaseCart();

        $lineItem = new LineItem($product->getId(), LineItem::PRODUCT_LINE_ITEM_TYPE, $product->getId(), $quantity);
        $lineItem->setLabel($product->getTranslation('name'));
        $lineItem->setGood(true);
        $lineItem->setStackable(true);
        $lineItem->setRemovable(true);

        $data = $cart->getData() ?? new CartDataCollection();

        $data->set('product-'.$product->getId(), $product);
        $cart->setData($data);

        $cart->getLineItems()->clear();
        $cart->add($lineItem);

        // disable Shopware\Core\Framework\Script\Execution\ScriptExecutor for performance reasons
        $disableExtensions = $_ENV['DISABLE_EXTENSIONS'] ?? null;
        $_ENV['DISABLE_EXTENSIONS'] = true;

        $cart =  $this->cartCalculator->calculate($cart, $salesChannelContext);

        if ($disableExtensions !== null) {
            $_ENV['DISABLE_EXTENSIONS'] = $disableExtensions;
        }

        return $cart;
    }
}
