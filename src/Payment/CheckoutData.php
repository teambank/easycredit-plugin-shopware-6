<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment;

use Shopware\Core\Framework\Struct\Struct;

class CheckoutData extends Struct
{
    /**
     * @var string
     */
    protected $paymentMethodId;

    /**
     * @var bool
     */
    protected $isSelected;

    /**
     * @var string
     */
    protected $paymentPlan;

    /**
     * @var string
     */
    protected $error;

    /**
     * @var string
     */
    protected $webshopId;

    /**
     * @var float
     */
    protected $grandTotal;

    /**
     * @var bool
     */
    protected $isPrefixValid;

   /**
     * @var boolean
     */
    protected $disableFlexprice;

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function getIsSelected(): bool
    {
        return $this->isSelected;
    }

    public function getPaymentPlan(): ?string
    {
        return $this->paymentPlan;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getWebshopId(): ?string
    {
        return $this->webshopId;
    }

    public function getGrandTotal(): ?float
    {
        return $this->grandTotal;
    }

    public function isPrefixValid(): bool
    {
        return $this->isPrefixValid;
    }

    public function getDisableFlexprice(): bool
    {
        return $this->disableFlexprice;
    }
}
