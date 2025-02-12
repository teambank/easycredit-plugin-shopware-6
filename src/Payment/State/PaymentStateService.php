<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment\State;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class PaymentStateService
{
    private EntityRepository $paymentStateRepository;

    public function __construct(
        EntityRepository $paymentStateRepository
    ) {
        $this->paymentStateRepository = $paymentStateRepository;
    }

    public function load($salesChannelContext)
    {
        $criteria = new Criteria([$salesChannelContext->getToken()]);

        return $this->paymentStateRepository
            ->search($criteria, $salesChannelContext->getContext())
            ->first();
    }

    public function save($salesChannelContext, $data)
    {
        if ($data) {
            $this->paymentStateRepository->upsert([
                [
                    'token' => $salesChannelContext->getToken(),
                    'payload' => $data,
                ],
            ], $salesChannelContext->getContext());
        } else {
            $this->paymentStateRepository->delete([
                [
                    'token' => $salesChannelContext->getToken(),
                ],
            ], $salesChannelContext->getContext());
        }
    }
}
