<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Teambank\EasyCreditApiV3\Integration\ValidationException;
use Teambank\EasyCreditApiV3\ApiException;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Helper\Quote as QuoteHelper;
use Netzkollektiv\EasyCredit\Api\Storage;

class CheckoutService {

    private IntegrationFactory $integrationFactory;

    private CartService $cartService;

    private QuoteHelper $quoteHelper;

    private Storage $storage;

    private LoggerInterface $logger;

    public function __construct(
        IntegrationFactory $integrationFactory,
        CartService $cartService,
        QuoteHelper $quoteHelper,
        Storage $storage,
        LoggerInterface $logger
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->cartService = $cartService;
        $this->quoteHelper = $quoteHelper;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    public function startCheckout ($salesChannelContext) {
        $checkout = $this->integrationFactory->createCheckout($salesChannelContext);
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $quote = $this->quoteHelper->getQuote($cart, $salesChannelContext);
        try {
            try {
                if (!$this->storage->get('express')) {
                    $checkout->isAvailable($quote);
                }
                $checkout->start($quote);
            } catch (ValidationException $e) {
                $this->storage->set('error',$e->getMessage());
            } catch (ApiException $e) {
                $response = \json_decode($e->getResponseBody());
                if ($response === null || !isset($response->violations)) {
                    throw new \Exception('violations could not be parsed');
                }
                $messages = [];
                foreach ($response->violations as $violation) {
                    $messages[] = $violation->message;
                }
                $this->logger->warning($e);
                $this->storage->set('error', \implode(' ',$messages));
            }
        } catch (\Throwable $e) {
            $this->logger->error($e);
            $this->storage->set('error', 'Es ist ein Fehler aufgetreten. Leider steht Ihnen easyCredit derzeit nicht zur Verfügung.');
        }
    }
}