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

    public array $data = [];

    public function __construct(
        Logger $logger,
        PaymentStateService $paymentStateService
    ) {
        $this->logger = $logger;
        $this->paymentStateService = $paymentStateService;
    }

    public function initialize(SalesChannelContext $salesChannelContext)
    {
        if ($this->data) {
            return;
        }

        $this->salesChannelContext = $salesChannelContext;
        $stateData = $this->paymentStateService->load($salesChannelContext);
        if ($stateData) {
            $this->logger->info('storage::initialize: ' . $this->salesChannelContext->getToken());
            $this->data = $stateData->getPayload();
        }
    }

    public function set($key, $value): self
    {
        $this->logger->debug('storage::set ' . $key . ' = (' . \gettype($value) . ') ' . $value);
        $this->data[$key] = $value;

        return $this;
    }

    public function get($key)
    {
        $value = $this->data[$key] ?? null;

        $this->logger->debug('storage::get ' . $key . ' = (' . \gettype($value) . ')' . $value);

        return $value;
    }

    public function clear(): self
    {
        $backtrace = \debug_backtrace();
        $this->logger->info('storage::clear from ' . $backtrace[1]['class'] . ':' . $backtrace[1]['function']);

        $this->data = [];
        $this->persist();

        return $this;
    }

    public function persist()
    {
        $this->logger->info('storage::persist: ' . $this->salesChannelContext->getToken());
        $this->paymentStateService->save($this->salesChannelContext, $this->data);
    }
}
