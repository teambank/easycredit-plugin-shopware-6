<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Helper;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Netzkollektiv\EasyCredit\Payment\Handler\InstallmentPaymentHandler;
use Netzkollektiv\EasyCredit\Payment\Handler\BillPaymentHandler;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;

class Payment
{
    private $paymentMethodRepository;

    private EntityRepository $salesChannelRepository;

    private PaymentHandlerRegistry $paymentHandlerRegistry;

    private ?EntityCollection $easyCreditMethods = null;

    private array $easyCreditHandlers = [];

    protected $salesChannelPaymentMethods = [];

    public function __construct(
        EntityRepository $paymentMethodRepository,
        EntityRepository $salesChannelRepository,
        PaymentHandlerRegistry $paymentHandlerRegistry
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->paymentHandlerRegistry = $paymentHandlerRegistry;
    }

    public function isSelected(SalesChannelContext $salesChannelContext, $paymentMethodId = null): bool
    {
        if ($paymentMethodId === null) {
            $paymentMethodId = $salesChannelContext->getPaymentMethod()->getId();
        }

        return $this->getEasyCreditMethods($salesChannelContext->getContext())
            ->filterByProperty('id', $paymentMethodId)
            ->count() > 0;
    }

    public function getPaymentMethodByPaymentType($paymentType, Context $context)
    {
        $paymentType = \str_replace('_PAYMENT', '', $paymentType);
        return $this->getEasyCreditMethods($context)
            ->filter(function (PaymentMethodEntity $paymentMethod) use ($paymentType) {
                return $this->getHandlerByPaymentMethod($paymentMethod)->getPaymentType() === $paymentType;
            })->first();
    }

    public function getHandlerByPaymentMethod($paymentMethod)
    {
        if (!isset($this->easyCreditHandlers[$paymentMethod->getId()])) {
            // prefer the newer getPaymentMethodHandler instead of getHandler (removed from v6.5)
            $this->easyCreditHandlers[$paymentMethod->getId()] = \method_exists($this->paymentHandlerRegistry, 'getPaymentMethodHandler') ?
                $this->paymentHandlerRegistry->getPaymentMethodHandler($paymentMethod->get('id')) :
                $this->paymentHandlerRegistry->getHandler($paymentMethod->getHandlerIdentifier());
        }
        return $this->easyCreditHandlers[$paymentMethod->getId()];
    }

    public function getEasyCreditMethods(Context $context): EntityCollection
    {
        if (!$this->easyCreditMethods) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsAnyFilter('handlerIdentifier', [
                InstallmentPaymentHandler::class,
                BillPaymentHandler::class
            ]));

            $this->easyCreditMethods = $this->paymentMethodRepository->search($criteria, $context)->getEntities();
        }
        return $this->easyCreditMethods;
    }

    public function getActivePaymentMethods(SalesChannelContext $salesChannelContext)
    {
        $paymentMethods = $this->getEasyCreditMethods($salesChannelContext->getContext())->filter(static function ($paymentMethod) {
            return $paymentMethod->get('active');
        });
        if (!$paymentMethods) {
            return false;
        }

        return $this->getSalesChannelPaymentMethods($salesChannelContext)
            ->filter(static function (PaymentMethodEntity $struct) use ($paymentMethods) {
                return \in_array($struct->get('id'), $paymentMethods->getIds());
            });
    }

    public function isEasyCreditInSalesChannel(SalesChannelContext $salesChannelContext): bool
    {
        return $this->getActivePaymentMethods($salesChannelContext)->count() > 0;
    }

    public function getCurrentPaymentMethod(SalesChannelContext $salesChannelContext)
    {
        return $this->getActivePaymentMethods($salesChannelContext)
            ->get($salesChannelContext->getPaymentMethod()->getId());
    }

    private function getSalesChannelPaymentMethods(SalesChannelContext $salesChannelContext): ?PaymentMethodCollection
    {
        if ($salesChannelContext->getSalesChannel()->getPaymentMethods()) {
            return $salesChannelContext->getSalesChannel()->getPaymentMethods();
        }
        if (isset($this->salesChannelPaymentMethods[$salesChannelContext->getToken()])) {
            return $this->salesChannelPaymentMethods[$salesChannelContext->getToken()];
        }

        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addAssociation('paymentMethods');

        /** @var SalesChannelEntity|null $result */
        $result = $this->salesChannelRepository->search($criteria, $salesChannelContext->getContext())
            ->get($salesChannelId);

        if (!$result) {
            return null;
        }
        $this->salesChannelPaymentMethods[$salesChannelContext->getToken()] = $result->getPaymentMethods();
        return $result->getPaymentMethods();
    }
}
