<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Util\Lifecycle;

use Netzkollektiv\EasyCredit\Helper\PaymentIdProvider;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;

class ActivateDeactivate
{
    public const ORDER_TRANSACTION_CUSTOM_FIELDS_EASYCREDIT_TRANSACTION_ID = 'easycredit_transaction_id';

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $customFieldRepository;

    /**
     * @var PaymentIdProvider
     */
    private $paymentIdProvider;

    public function __construct(
        PaymentIdProvider $paymentIdProvider,
        EntityRepositoryInterface $paymentRepository,
        EntityRepositoryInterface $customFieldRepository
    ) {
        $this->paymentIdProvider = $paymentIdProvider;
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
        $paymentMethodId = $this->paymentIdProvider->getPaymentMethodId($context);

        if ($paymentMethodId === null) {
            return;
        }

        $updateData[] = [
            'id' => $paymentMethodId,
            'active' => $active,
        ];

        $this->paymentRepository->update($updateData, $context);
    }

    private function activateOrderTransactionCustomField(Context $context): void
    {
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');
        $customFieldIds = $this->getCustomFieldIds($customFieldRepository, $context);

        if ($customFieldIds->getTotal() !== 0) {
            return;
        }

        $customFieldRepository->upsert(
            [
                [
                    'name' => self::ORDER_TRANSACTION_CUSTOM_FIELDS_EASYCREDIT_TRANSACTION_ID,
                    'type' => CustomFieldTypes::TEXT,
                ],
            ],
            $context
        );
    }

    private function deactivateOrderTransactionCustomField(Context $context): void
    {
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository');
        $customFieldIds = $this->getCustomFieldIds($customFieldRepository, $context);

        if ($customFieldIds->getTotal() !== 0) {
            return;
        }

        $ids = [];
        foreach ($customFieldIds->getIds() as $customFieldId) {
            $ids[] = ['id' => $customFieldId];
        }
        $customFieldRepository->delete($ids, $context);
    }

    private function getCustomFieldIds(EntityRepositoryInterface $customFieldRepository, Context $context): IdSearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::ORDER_TRANSACTION_CUSTOM_FIELDS_EASYCREDIT_TRANSACTION_ID));

        return $customFieldRepository->searchIds($criteria, $context);
    }
}
