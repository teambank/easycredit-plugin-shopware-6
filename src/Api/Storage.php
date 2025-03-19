<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use Monolog\Logger;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Teambank\EasyCreditApiV3\Integration\StorageInterface;
use Netzkollektiv\EasyCredit\Payment\State\PaymentStateService;

class Storage implements StorageInterface
{
    protected Logger $logger;

    protected PaymentStateService $paymentStateService;

    private SalesChannelContext $salesChannelContext;

    private array $data = [];

    public function __construct(
        Logger $logger,
        PaymentStateService $paymentStateService
    ) {
        $this->logger = $logger;
        $this->paymentStateService = $paymentStateService;
    }

    public function initialize(SalesChannelContext $salesChannelContext): self
    {
        if ($this->data) {
            return $this;
        }

        $this->salesChannelContext = $salesChannelContext;
        $stateData = $this->paymentStateService->load($salesChannelContext);

        $this->logger->debug('storage::initialize: ' . $this->salesChannelContext->getToken());
        if ($stateData) {
            $this->data = $stateData->getPayload();
        }

        return $this;
    }

    public function set($key, $value): self
    {
        $this->logger->debug(\sprintf('storage::set %s = (%s) %s', $key, \gettype($value), $value));
        $this->data[$key] = $value;

        return $this;
    }

    public function get($key)
    {
        $value = $this->data[$key] ?? null;
        $this->logger->debug(\sprintf('storage::get %s = (%s) %s', $key, \gettype($value), $value));

        return $value;
    }

    public function clear(): self
    {
        $backtrace = \debug_backtrace();
        $caller = $backtrace[1]['class'] . ':' . $backtrace[1]['function'];
        $this->logger->debug(\sprintf('storage::clear from %s', $caller));
        $this->data = [];
        $this->persist();

        return $this;
    }

    public function persist(): void
    {
        if (!isset($this->salesChannelContext)) {
            $this->logger->error('storage::persist called before initialization.');
            return;
        }

        $this->logger->debug('storage::persist: ' . $this->salesChannelContext->getToken());
        $this->paymentStateService->save($this->salesChannelContext, $this->data);
    }
}
