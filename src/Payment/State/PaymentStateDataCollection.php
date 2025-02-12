<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment\State;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(PaymentStateDataEntity $entity)
 * @method void set(string $key, PaymentStateDataEntity $entity)
 * @method PaymentStateDataEntity[] getIterator()
 * @method PaymentStateDataEntity[] getElements()
 * @method PaymentStateDataEntity|null get(string $key)
 * @method PaymentStateDataEntity|null first()
 * @method PaymentStateDataEntity|null last()
 */
class PaymentStateDataCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PaymentStateDataEntity::class;
    }
}
