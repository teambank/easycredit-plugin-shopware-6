<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api\Quote;

use Teambank\EasyCreditApiV3\Model\Customer;
use Teambank\EasyCreditApiV3\Model\Contact;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Teambank\EasyCreditApiV3\Integration\Util\PrefixConverter;

class CustomerBuilder
{
    protected $customer;

    protected $billingAddress;

    protected $prefixConverter;

    public function __construct(PrefixConverter $prefixConverter)
    {
        $this->prefixConverter = $prefixConverter;
    }

    public function getPrefix(): ?string
    {
        $prefix = null;
        if ($this->customer->getSalutation()) {
            $prefix = $this->customer->getSalutation()->getDisplayName();
        }
        if ($this->customer->getActiveBillingAddress()->getSalutation()) {
            $prefix = $this->customer->getActiveBillingAddress()->getSalutation()->getDisplayName();
        }

        return $this->prefixConverter->convert($prefix);
    }

    public function getFirstName(): string
    {
        if ($this->customer->getGuest()) {
            return $this->customer->getActiveBillingAddress()->getFirstName();
        }

        return $this->customer->getFirstName();
    }

    public function getLastName(): string
    {
        if ($this->customer->getGuest()) {
            return $this->customer->getActiveBillingAddress()->getLastName();
        }

        return $this->customer->getLastName();
    }

    public function getEmail(): string
    {
        return $this->customer->getEmail();
    }

    public function getDob(): ?string
    {
        if ($this->customer->getBirthday() instanceof \DateTimeImmutable) {
            return $this->customer->getBirthday()->format('Y-m-d');
        }

        return null;
    }

    public function getCreatedAt(): ?string
    {
        if ($this->customer->getCreatedAt() instanceof \DateTimeImmutable) {
            return $this->customer->getCreatedAt()->format('Y-m-d');
        }

        return null;
    }

    public function getOrderCount(): int
    {
        return $this->customer->getOrderCount();
    }

    public function build(
        ?CustomerEntity $customer
    ) {
        $this->customer = $customer;

        return new Customer([
            'gender' => $this->getPrefix(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'birthDate' => $this->getDob(),
            'contact' => new Contact([
                'email' => $this->customer->getEmail()
            ]),
            'companyName' => $this->customer->getActiveBillingAddress() ? $this->customer->getActiveBillingAddress()->getCompany() : null
        ]);
    }
}
