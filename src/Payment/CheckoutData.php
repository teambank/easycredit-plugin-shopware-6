<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Payment;

use Shopware\Core\Framework\Struct\Struct;

class CheckoutData extends Struct
{
    /**
     * @var string
     */
    protected $paymentMethodId;

    /**
     * @var string
     */
    protected $agreement;

    /**
     * @var string
     */
    protected $paymentPlan;

    /**
     * @var string
     */
    protected $error;

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function getAgreement(): string
    {
        return $this->agreement;
    }

    public function getPaymentPlan(): ?string
    {
        return $this->paymentPlan;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
