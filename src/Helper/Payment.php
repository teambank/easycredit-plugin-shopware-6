<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Helper;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

use Teambank\RatenkaufByEasyCreditApiV3\Integration\ValidationException;
use Teambank\RatenkaufByEasyCreditApiV3\ApiException;
use Netzkollektiv\EasyCredit\Payment\Handler as PaymentHandler;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Helper\Quote as QuoteHelper;
use Netzkollektiv\EasyCredit\Api\Storage;

class Payment
{
    private $paymentMethodRepository;

    private EntityRepository $salesChannelRepository;

    private IntegrationFactory $integrationFactory;

    private CartService $cartService;

    private QuoteHelper $quoteHelper;

    private Storage $storage;

    private LoggerInterface $logger;

    private array $paymentMethodIdCache = [];

    public function __construct(
        EntityRepository $paymentMethodRepository,
        EntityRepository $salesChannelRepository,
        IntegrationFactory $integrationFactory,
        CartService $cartService,
        QuoteHelper $quoteHelper,
        Storage $storage,
        LoggerInterface $logger
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->salesChannelRepository = $salesChannelRepository;

        $this->integrationFactory = $integrationFactory;
        $this->cartService = $cartService;
        $this->quoteHelper = $quoteHelper;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    public function isSelected(SalesChannelContext $salesChannelContext, $paymentMethodId = null): bool
    {
        if ($paymentMethodId === null) {
            $paymentMethodId = $salesChannelContext->getPaymentMethod()->getId();
        }

        return $this->getPaymentMethodId($salesChannelContext->getContext()) === $paymentMethodId;
    }

    public function getPaymentMethodId(Context $context): ?string
    {
        $cacheId = \sha1(\json_encode($context));
        if (!isset($this->paymentMethodIdCache[$cacheId])) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('handlerIdentifier', PaymentHandler::class));

            $this->paymentMethodIdCache[$cacheId] = $this->paymentMethodRepository->searchIds($criteria, $context)->firstId();
        }
        return $this->paymentMethodIdCache[$cacheId];
    }

    public function isPaymentMethodInSalesChannel(SalesChannelContext $salesChannelContext): bool
    {
        $context = $salesChannelContext->getContext();
        $paymentMethodId = $this->getPaymentMethodId($context);
        if (!$paymentMethodId) {
            return false;
        }

        $paymentMethods = $this->getSalesChannelPaymentMethods($salesChannelContext);
        if (!$paymentMethods) {
            return false;
        }

        if ($paymentMethods->get($paymentMethodId) instanceof PaymentMethodEntity) {
            return true;
        }

        return false;
    }

    public function getPaymentMethod(SalesChannelContext $salesChannelContext) {
        return $this->getSalesChannelPaymentMethods($salesChannelContext)
            ->get($this->getPaymentMethodId($salesChannelContext->getContext()));
    }

    private function getSalesChannelPaymentMethods(SalesChannelContext $salesChannelContext): ?PaymentMethodCollection {
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addAssociation('paymentMethods');
        /** @var SalesChannelEntity|null $result */
        $result = $this->salesChannelRepository->search($criteria, $salesChannelContext->getContext())
            ->get($salesChannelId);

        if (!$result) {
            return null;
        }

        return $result->getPaymentMethods();
    }

    public function startCheckout ($salesChannelContext) {
        $checkout = $this->integrationFactory->createCheckout($salesChannelContext);
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext, false);
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
            $this->storage->set('error', 'Es ist ein Fehler aufgetreten. Leider steht Ihnen easyCredit-Ratenkauf derzeit nicht zur Verfügung.');
        }        
    }
}
