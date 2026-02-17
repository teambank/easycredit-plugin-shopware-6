<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Message;

class FetchWebshopInfoMessage
{

    private string $salesChannelId;

    public function __construct(
        string $salesChannelId
    ) {
        $this->salesChannelId = $salesChannelId;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }
}
