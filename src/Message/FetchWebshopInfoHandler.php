<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Message;

use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
class FetchWebshopInfoHandler
{
    private const CACHE_KEY_PREFIX = 'easycredit-webshop-details-';
    private const CACHE_TTL = 3600;

    private IntegrationFactory $integrationFactory;

    private CacheItemPoolInterface $cache;

    private LoggerInterface $logger;

    public function __construct(
        IntegrationFactory $integrationFactory,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function __invoke(FetchWebshopInfoMessage $message): void
    {
        $salesChannelId = $message->getSalesChannelId();
        $cacheKey = self::CACHE_KEY_PREFIX . $salesChannelId;

        try {
            $response = $this->integrationFactory
                ->createCheckout($salesChannelId)
                ->getWebshopDetails();

            $item = $this->cache->getItem($cacheKey);
            $item->set(['response' => $response, 'cached_at' => time()]);
            $item->expiresAfter(self::CACHE_TTL);
            $this->cache->save($item);

            $this->logger->debug('EasyCredit webshop details fetched and cached by queue', [
                'salesChannelId' => $salesChannelId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('EasyCredit queue: failed to fetch webshop details: ' . $e->getMessage(), [
                'exception' => $e,
                'salesChannelId' => $salesChannelId,
            ]);
        }
    }
}
