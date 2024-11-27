<?php

declare(strict_types=1);
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
     * @var array
     */
    protected $paymentMethodIds;

    /**
     * @var string
     */
    protected $selectedPaymentMethod;

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
     * @var boolean
     */
    protected $approved;

    /**
     * @var float
     */
    protected $grandTotal;

   /**
     * @var boolean
     */
    protected $disableFlexprice;

    public function getPaymentMethodIds(): array
    {
        return $this->paymentMethodIds;
    }

    public function getSelectedPaymentMethod(): string
    {
        return $this->selectedPaymentMethod;
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

    public function getApproved(): bool
    {
        return $this->approved;
    }

    public function getDisableFlexprice(): bool
    {
        return $this->disableFlexprice;
    }
}
