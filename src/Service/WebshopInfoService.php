<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Service;

use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Teambank\EasyCreditApiV3\Model\WebshopResponse;
use Psr\Log\LoggerInterface;

class WebshopInfoService
{
    private IntegrationFactory $integrationFactory;
    private AdapterInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        IntegrationFactory $integrationFactory,
        AdapterInterface $cache,
        LoggerInterface $logger
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getWebshopInfo(string $salesChannelId): WebshopResponse
    {
        if (!\method_exists($this->cache,'get')) { // no cache for <= sw 6.4.9
            return $this->integrationFactory
                ->createCheckout($salesChannelId)
                ->getWebshopDetails();
        }

        return $this->cache->get('easycredit-webshop-details-' . $salesChannelId, function (ItemInterface $item) use ($salesChannelId) {
            $item->expiresAfter(3600); // Cache for 1 hour
            try {
                return $this->integrationFactory
                    ->createCheckout($salesChannelId)
                    ->getWebshopDetails();
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch webshop details: ' . $e->getMessage(), [
                    'exception' => $e,
                    'salesChannelId' => $salesChannelId
                ]);
                throw $e; // Re-throw to prevent caching
            }
        });
    }
} 