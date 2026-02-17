<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Netzkollektiv\EasyCredit\Message\FetchWebshopInfoMessage;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Teambank\EasyCreditApiV3\Model\WebshopResponse;
use Psr\Log\LoggerInterface;

class WebshopInfoService
{
    private const CACHE_KEY_PREFIX = 'easycredit-webshop-details-';
    private const MESSENGER_TABLE = 'messenger_messages';
    private const CACHE_TTL = 3600;
    /** Refresh cache when less than this many seconds remain until expiry (e.g. 10 min before 1h) */
    private const REFRESH_BEFORE_EXPIRY = 600;

    private CacheItemPoolInterface $cache;
    private LoggerInterface $logger;
    private MessageBusInterface $messageBus;
    private Connection $connection;

    public function __construct(
        CacheItemPoolInterface $cache,
        LoggerInterface $logger,
        MessageBusInterface $messageBus,
        Connection $connection
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->messageBus = $messageBus;
        $this->connection = $connection;
    }

    public function getWebshopInfo(string $salesChannelId): WebshopResponse
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $salesChannelId;
        $item = $this->cache->getItem($cacheKey);

        if (!$item->isHit()) {
            $this->dispatchFetchWebshopInfoIfNeeded($salesChannelId);
            return $this->createEmptyWebshopResponse();
        }

        /** @var array{response: WebshopResponse, cached_at: int} $raw */
        $raw = $item->get();
        $age = time() - $raw['cached_at'];
        $needsRefresh = $age >= (self::CACHE_TTL - self::REFRESH_BEFORE_EXPIRY);

        if ($needsRefresh) {
            $this->dispatchFetchWebshopInfoIfNeeded($salesChannelId);
        }

        return $raw['response'];
    }

    private function dispatchFetchWebshopInfoIfNeeded(string $salesChannelId): bool
    {
        if (!$this->shouldDispatchFetch($salesChannelId)) {
            return false;
        }

        $this->messageBus->dispatch(new FetchWebshopInfoMessage($salesChannelId));
        return true;
    }

    /**
     * Check whether we should dispatch a fetch (no matching message in the queue yet).
     * Returns true if caller should dispatch, false if a FetchWebshopInfoMessage for this sales channel is already in the queue.
     * Compares message class + salesChannelId at SQL level by extracting JSON from headers/body (MySQL/MariaDB).
     */
    private function shouldDispatchFetch(string $salesChannelId): bool
    {
        try {
            $messageClass = FetchWebshopInfoMessage::class;

            $row = $this->connection->fetchOne(
                'SELECT 1 FROM ' . self::MESSENGER_TABLE . '
                WHERE delivered_at IS NULL
                AND JSON_UNQUOTE(JSON_EXTRACT(headers, \'$.type\')) = :messageClass
                AND JSON_UNQUOTE(JSON_EXTRACT(body, \'$.salesChannelId\')) = :salesChannelId
                LIMIT 1',
                [
                    'messageClass' => $messageClass,
                    'salesChannelId' => $salesChannelId,
                ],
                [
                    'messageClass' => ParameterType::STRING,
                    'salesChannelId' => ParameterType::STRING,
                ]
            );

            return $row === false;
        } catch (\Throwable $e) {
            $this->logger->warning('Could not check messenger queue for pending FetchWebshopInfoMessage', [
                'salesChannelId' => $salesChannelId,
                'error' => $e->getMessage(),
            ]);
            return true;
        }
    }

    private function createEmptyWebshopResponse(): WebshopResponse
    {
        return new WebshopResponse([]);
    }
} 
