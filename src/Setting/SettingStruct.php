<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Setting;

use Shopware\Core\Framework\Struct\Struct;

class SettingStruct extends Struct
{
    /**
     * @var string
     */
    protected $webshopId;

    /**
     * @var string
     */
    protected $apiPassword;

    /**
     * @var string
     */
    protected $apiSignature;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var bool
     */
    protected $markShipped = true;

    /**
     * @var bool
     */
    protected $markRefunded = true;

    /**
     * @var string|null
     */
    protected $paymentStatus = null;

    /**
     * @var string|null
     */
    protected $orderStatus = null;

    /**
     * @var bool
     */
    protected $removeInterest = true;

    /**
     * @var string|null
     */
    protected $clickAndCollectShippingMethod = null;

    /**
     * @var bool
     */
    protected $modalEnabled = false;

    /**
     * @var string|null
     */
    protected $modalSettingsDelay = null;

    /**
     * @var string|null
     */
    protected $modalSettingsMedia = null;

    /**
     * @var string|null
     */
    protected $modalSettingsSnoozeFor = null;

    /**
     * @var bool
     */
    protected $flashboxEnabled = false;

    /**
     * @var string|null
     */
    protected $flashboxSettingsMedia = null;

    /**
     * @var bool
     */
    protected $barEnabled = false;

    /**
     * @var bool
     */
    protected $cardEnabled = false;

    /**
     * @var string|null
     */
    protected $cardSettingsPosition = null;

    /**
     * @var string|null
     */
    protected $cardSettingsMedia = null;

    /**
     * @var bool
     */
    protected $cardSearchEnabled = false;

    /**
     * @var bool
     */
    protected $widgetEnabled = true;

    /**
     * @var string|null
     */
    protected $widgetSelectorProductDetail = '.product-detail-buy .product-detail-tax-container';

    /**
     * @var string|null
     */
    protected $widgetSelectorProductListing = '.cms-element-product-listing .product-box .product-price-wrapper easycredit-widget[display-type=minimal]';

    /**
     * @var string|null
     */
    protected $widgetSelectorCart = '.checkout-aside-action:not(.d-grid)';

    /**
     * @var string|null
     */
    protected $widgetSelectorOffCanvasCart = '.offcanvas-summary easycredit-widget[display-type=minimal]';

    /**
     * @var bool
     */
    protected $expressProductEnabled = true;

    /**
     * @var bool
     */
    protected $expressCartEnabled = true;

    protected ?string $webshopInfo = null;

    public function getWebshopId(): ?string
    {
        return $this->webshopId;
    }

    public function setWebshopId(string $webshopId): void
    {
        $this->webshopId = $webshopId;
    }

    public function getApiPassword(): ?string
    {
        return $this->apiPassword;
    }

    public function setApiPassword(string $apiPassword): void
    {
        $this->apiPassword = $apiPassword;
    }

    public function getApiSignature(): ?string
    {
        return $this->apiSignature;
    }

    public function setApiSignature(string $apiSignature): void
    {
        $this->apiSignature = $apiSignature;
    }

    public function getDebug(): bool
    {
        return (bool) $this->debug;
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function getMarkShipped(): bool
    {
        return (bool) $this->markShipped;
    }

    public function setMarkShipped(bool $markShipped): void
    {
        $this->markShipped = $markShipped;
    }

    public function getMarkRefunded(): bool
    {
        return (bool) $this->markRefunded;
    }

    public function setMarkRefunded(bool $markRefunded): void
    {
        $this->markRefunded = $markRefunded;
    }

    public function getOrderStatus(): string
    {
        return (string) $this->orderStatus;
    }

    public function setOrderStatus(string $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
    }

    public function getPaymentStatus(): string
    {
        return (string) $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): void
    {
        $this->paymentStatus = $paymentStatus;
    }

    public function getRemoveInterest(): bool
    {
        return (bool) $this->removeInterest;
    }

    public function setRemoveInterest(bool $removeInterest): void
    {
        $this->removeInterest = $removeInterest;
    }

    public function getClickAndCollectShippingMethod(): ?string
    {
        return $this->clickAndCollectShippingMethod;
    }

    public function setClickAndCollectShippingMethod(string $shippingMethod): void
    {
        $this->clickAndCollectShippingMethod = $shippingMethod;
    }

    public function getModalEnabled(): bool
    {
        return (bool) $this->modalEnabled;
    }

    public function setModalEnabled(bool $modalEnabled): void
    {
        $this->modalEnabled = $modalEnabled;
    }

    public function getModalSettingsDelay(): ?string
    {
        return $this->modalSettingsDelay;
    }

    public function setModalSettingsDelay(string $modalSettingsDelay): void
    {
        $this->modalSettingsDelay = $modalSettingsDelay;
    }

    public function getModalSettingsMedia(): ?string
    {
        return $this->modalSettingsMedia;
    }

    public function setModalSettingsMedia(string $modalSettingsMedia): void
    {
        $this->modalSettingsMedia = $modalSettingsMedia;
    }

    public function getModalSettingsSnoozeFor(): ?string
    {
        return $this->modalSettingsSnoozeFor;
    }

    public function setModalSettingsSnoozeFor(string $modalSettingsSnoozeFor): void
    {
        $this->modalSettingsSnoozeFor = $modalSettingsSnoozeFor;
    }

    public function getFlashboxEnabled(): bool
    {
        return (bool) $this->flashboxEnabled;
    }

    public function setFlashboxEnabled(bool $flashboxEnabled): void
    {
        $this->flashboxEnabled = $flashboxEnabled;
    }

    public function getFlashboxSettingsMedia(): ?string
    {
        return $this->flashboxSettingsMedia;
    }

    public function setFlashboxSettingsMedia(string $flashboxSettingsMedia): void
    {
        $this->flashboxSettingsMedia = $flashboxSettingsMedia;
    }

    public function getBarEnabled(): bool
    {
        return (bool) $this->barEnabled;
    }

    public function setBarEnabled(bool $barEnabled): void
    {
        $this->barEnabled = $barEnabled;
    }

    public function getCardEnabled(): bool
    {
        return (bool) $this->cardEnabled;
    }

    public function setCardEnabled(bool $cardEnabled): void
    {
        $this->cardEnabled = $cardEnabled;
    }

    public function getCardSearchEnabled(): bool
    {
        return (bool) $this->cardSearchEnabled;
    }

    public function setCardSearchEnabled(bool $cardSearchEnabled): void
    {
        $this->cardSearchEnabled = $cardSearchEnabled;
    }

    public function getCardSettingsPosition(): ?string
    {
        return $this->cardSettingsPosition;
    }

    public function setCardSettingsPosition(string $cardSettingsPosition): void
    {
        $this->cardSettingsPosition = $cardSettingsPosition;
    }

    public function getCardSettingsMedia(): ?string
    {
        return $this->cardSettingsMedia;
    }

    public function setCardSettingsMedia(string $cardSettingsMedia): void
    {
        $this->cardSettingsMedia = $cardSettingsMedia;
    }

    public function getWidgetEnabled(): bool
    {
        return (bool) $this->widgetEnabled;
    }

    public function setWidgetEnabled(bool $widgetEnabled): void
    {
        $this->widgetEnabled = $widgetEnabled;
    }

    public function setWidgetSelectorProductDetail(string $widgetSelectorProductDetail): void
    {
        $this->widgetSelectorProductDetail = $widgetSelectorProductDetail;
    }

    public function getWidgetSelectorProductDetail(): ?string
    {
        return $this->widgetSelectorProductDetail;
    }

    public function setWidgetSelectorProductListing(string $widgetSelectorProductListing): void
    {
        $this->widgetSelectorProductListing = $widgetSelectorProductListing;
    }

    public function getWidgetSelectorProductListing(): ?string
    {
        return $this->widgetSelectorProductListing;
    }

    public function getWidgetSelectorCart(): ?string
    {
        return $this->widgetSelectorCart;
    }

    public function setWidgetSelectorCart(string $widgetSelectorCart): void
    {
        $this->widgetSelectorCart = $widgetSelectorCart;
    }

    public function getWidgetSelectorOffCanvasCart(): ?string
    {
        return $this->widgetSelectorOffCanvasCart;
    }

    public function setWidgetSelectorOffCanvasCart(string $widgetSelectorOffCanvasCart): void
    {
        $this->widgetSelectorOffCanvasCart = $widgetSelectorOffCanvasCart;
    }

    public function getExpressProductEnabled(): bool
    {
        return (bool) $this->expressProductEnabled;
    }

    public function setExpressProductEnabled(bool $expressProductEnabled): void
    {
        $this->expressProductEnabled = $expressProductEnabled;
    }

    public function getExpressCartEnabled(): bool
    {
        return (bool) $this->expressCartEnabled;
    }

    public function setExpressCartEnabled(bool $expressCartEnabled): void
    {
        $this->expressCartEnabled = $expressCartEnabled;
    }

    public function getWebshopInfo(): ?\stdClass
    {
        return $this->webshopInfo ? \json_decode($this->webshopInfo) : null;
    }

    public function setWebshopInfo(array $webshopInfo): void
    {
        $this->webshopInfo = \json_encode($webshopInfo);
    }
}
