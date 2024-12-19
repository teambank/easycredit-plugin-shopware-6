<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Util\Lifecycle;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Netzkollektiv\EasyCredit\EasyCreditRatenkauf;

class ActivateDeactivate
{
    private EntityRepository $paymentRepository;

    private EntityRepository $customFieldRepository;

    /**
     * @var PaymentHelper
     */
    private PaymentHelper $paymentHelper;

    public function __construct(
        PaymentHelper $paymentHelper,
        EntityRepository $paymentRepository,
        EntityRepository $customFieldRepository
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->paymentRepository = $paymentRepository;
        $this->customFieldRepository = $customFieldRepository;
    }

    public function activate(Context $context): void
    {
        $this->setPaymentMethodsIsActive(true, $context);
        $this->activateOrderTransactionCustomField($context);
    }

    public function deactivate(Context $context): void
    {
        $this->setPaymentMethodsIsActive(false, $context);
        $this->deactivateOrderTransactionCustomField($context);
    }

    private function setPaymentMethodsIsActive(bool $active, Context $context): void
    {
        $paymentMethods = $this->paymentHelper->getEasyCreditMethods($context);

        if ($paymentMethods->count() === 0) {
            return;
        }

        $updateData = [];
        foreach ($paymentMethods as $method) {
            $updateData[] = [
                'id' => $method->get('id'),
                'active' => $active,
            ];
        }

        $this->paymentRepository->update($updateData, $context);
    }

    private function activateOrderTransactionCustomField(Context $context): void
    {
        $customFieldIds = $this->getCustomFieldIds($context);

        if ($customFieldIds->getTotal() !== 0) {
            return;
        }

        $this->customFieldRepository->upsert(
            [
                [
                    'name' => EasyCreditRatenkauf::ORDER_TRANSACTION_CUSTOM_FIELDS_EASYCREDIT_TRANSACTION_ID,
                    'type' => CustomFieldTypes::TEXT,
                ],
            ],
            $context
        );
    }

    private function deactivateOrderTransactionCustomField(Context $context): void
    {
        $customFieldIds = $this->getCustomFieldIds($context);

        if ($customFieldIds->getTotal() === 0) {
            return;
        }

        $ids = \array_map(static fn ($id) => ['id' => $id], $customFieldIds->getIds());
        $this->customFieldRepository->delete($ids, $context);
    }

    private function getCustomFieldIds(Context $context): IdSearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', EasyCreditRatenkauf::ORDER_TRANSACTION_CUSTOM_FIELDS_EASYCREDIT_TRANSACTION_ID));

        return $this->customFieldRepository->searchIds($criteria, $context);
    }
}
