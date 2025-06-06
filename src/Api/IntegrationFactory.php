<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use GuzzleHttp\Client;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;
use Teambank\EasyCreditApiV3 as Api;

use Psr\Log\LoggerInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\MessageFormatter;
use Netzkollektiv\EasyCredit\Api\Storage;

class IntegrationFactory
{
    protected $settings;

    protected $logger;

    protected $storage;

    public function __construct(
        SettingsServiceInterface $settingsService,
        LoggerInterface $logger,
        Storage $storage
    ) {
        $this->settings = $settingsService;
        $this->logger = $logger;
        $this->storage = $storage;
    }

    protected function getClient()
    {
        $stack = HandlerStack::create();
        $stack->push(
            Middleware::log(
                $this->logger,
                new MessageFormatter(MessageFormatter::DEBUG),
            )
        );
        return new Client([
            'debug' => false,
            'handler' => $stack
        ]);
    }

    protected function getConfig(?string $salesChannelId = null, bool $validateSettings = true) 
    {
        $settings = $this->settings->getSettings($salesChannelId, $validateSettings);

        return Api\Configuration::getDefaultConfiguration()
            ->setHost('https://ratenkauf.easycredit.de')
            ->setUsername($settings->getWebshopId())
            ->setPassword($settings->getApiPassword())
            ->setAccessToken($settings->getApiSignature());
    }

    public function createCheckout(?string $salesChannelId = null): Api\Integration\Checkout
    {
        $client = $this->getClient();
        $config = $this->getConfig($salesChannelId);

        $webshopApi = new Api\Service\WebshopApi(
            $client,
            $config
        );
        $transactionApi = new Api\Service\TransactionApi(
            $client,
            $config
        );
        $installmentplanApi = new Api\Service\InstallmentplanApi(
            $client,
            $config
        );

        return new Api\Integration\Checkout(
            $webshopApi,
            $transactionApi,
            $installmentplanApi,
            $this->storage,
            new Api\Integration\Util\AddressValidator(),
            new Api\Integration\Util\PrefixConverter(),
            $this->logger
        );
    }

    public function createTransactionApi(): Api\Service\TransactionApi
    {
        $client = $this->getClient();
        $config = $this->getConfig()
            ->setHost('https://partner.easycredit-ratenkauf.de');

        return new Api\Service\TransactionApi(
            $client,
            $config
        );
    }
}
