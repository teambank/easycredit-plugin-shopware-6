<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Controller;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\EasyCreditRatenkauf;
use Teambank\EasyCreditApiV3\ApiException;
use Teambank\EasyCreditApiV3\Model\CaptureRequest;
use Teambank\EasyCreditApiV3\Model\RefundRequest;

class TransactionsController extends AbstractController
{
    private IntegrationFactory $integrationFactory;

    private EntityRepository $orderTransactionRepository;

    public function __construct(
        IntegrationFactory $integrationFactory,
        EntityRepository $orderTransactionRepository
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    protected function getJsonResponseFromException ($response) {
        $response = new Response($response->getResponseBody(), $response->getCode());
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function getTransaction(Request $request, $transactionId): Response
    {
        try {
            $transaction = $this->integrationFactory
                ->createTransactionApi($this->resolveSalesChannelId($request, (string) $transactionId))
                ->apiMerchantV3TransactionTransactionIdGet($transactionId);

            return new JsonResponse($transaction);
        } catch (ApiException $e) {
            return $this->getJsonResponseFromException($e);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function captureTransaction(Request $request, $transactionId): Response
    {
        try {
            $params = $request->request->all();

            $this->integrationFactory
                ->createTransactionApi($this->resolveSalesChannelId($request, (string) $transactionId))
                ->apiMerchantV3TransactionTransactionIdCapturePost(
                    $transactionId,
                    new CaptureRequest(['trackingNumber' => isset($params['trackingNumber']) ? $params['trackingNumber'] : null])
                );

            return new JsonResponse();
        } catch (ApiException $e) {
            return $this->getJsonResponseFromException($e);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function refundTransaction(Request $request, $transactionId): Response
    {
        try {
            $params = $request->request->all();

            $this->integrationFactory
                ->createTransactionApi($this->resolveSalesChannelId($request, (string) $transactionId))
                ->apiMerchantV3TransactionTransactionIdRefundPost(
                    $transactionId,
                    new RefundRequest(['value' => $params['value'] ?? null])
                );

                return new JsonResponse();
        } catch (ApiException $e) {
            return $this->getJsonResponseFromException($e);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function resolveSalesChannelId(Request $request, string $transactionId): ?string
    {
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);
        if (!$context instanceof Context) {
            $context = Context::createDefaultContext();
        }

        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addAssociation('order');
        $criteria->addFilter(new EqualsFilter(
            'customFields.' . EasyCreditRatenkauf::ORDER_TRANSACTION_CUSTOM_FIELDS_EASYCREDIT_TRANSACTION_ID,
            $transactionId
        ));

        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();
        if (!$orderTransaction instanceof OrderTransactionEntity) {
            return null;
        }

        $order = $orderTransaction->getOrder();
        if (!$order instanceof OrderEntity) {
            return null;
        }

        $salesChannelId = $order->getSalesChannelId();

        return $salesChannelId !== '' ? $salesChannelId : null;
    }
}
