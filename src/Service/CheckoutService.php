<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Teambank\EasyCreditApiV3\Integration\ValidationException;
use Teambank\EasyCreditApiV3\ApiException;
use Teambank\EasyCreditApiV3\Model\Transaction;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\Logger\DebugLogger;

class CheckoutService
{
    private IntegrationFactory $integrationFactory;

    private Storage $storage;

    private LoggerInterface $logger;

    private DebugLogger $debugLogger;

    public function __construct(
        IntegrationFactory $integrationFactory,
        Storage $storage,
        LoggerInterface $logger,
        DebugLogger $debugLogger
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->storage = $storage;
        $this->logger = $logger;
        $this->debugLogger = $debugLogger;
    }

    public function startCheckout(SalesChannelContext $salesChannelContext, Transaction $quote)
    {
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $this->debugLogger->debug('payment::startCheckout', $salesChannelId);

        try {
            $checkout = $this->integrationFactory->createCheckout($salesChannelId);

            if (!$this->storage->get('express')) {
                $checkout->isAvailable($quote);
            }
            $checkout->start($quote);

            $this->debugLogger->debug('payment::startCheckout complete', $salesChannelId);
        } catch (ValidationException $e) {
            $this->debugLogger->debug(
                'payment::startCheckout failed ValidationException: ' . $e->getMessage(),
                $salesChannelId
            );
            $this->storage->set('error', $e->getMessage());
        } catch (ApiException $e) {
            $this->debugLogger->debug(
                'payment::startCheckout failed ApiException: ' . $e->getMessage(),
                $salesChannelId
            );
            $response = \json_decode($e->getResponseBody());
            if (isset($response->violations)) {
                $this->logger->warning($e->getMessage(), ['exception' => $e]);
                $this->storage->set('error', \implode(' ', \array_map(fn($v) => $v->message, $response->violations)));
            } else {
                $this->logger->error('EasyCredit API error: Invalid response format', [
                    'response' => $e->getResponseBody(),
                    'exception' => $e
                ]);
                $this->storage->set('error', 'Es ist ein unerwarteter Fehler aufgetreten.');
            }
        } catch (\Throwable $e) {
            $this->debugLogger->debug(
                'payment::startCheckout failed: ' . $e->getMessage(),
                $salesChannelId
            );
            $this->logger->error($e);
            $this->storage->set('error', 'Es ist ein Fehler aufgetreten. Leider steht Ihnen easyCredit derzeit nicht zur Verfügung.');
        }

        $this->storage->persist();
    }
}
