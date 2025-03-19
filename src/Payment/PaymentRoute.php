<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Payment;

use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\Helper\Quote as QuoteHelper;
use Netzkollektiv\EasyCredit\Payment\PaymentRouteResponse;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\Service\CheckoutService;
use Netzkollektiv\EasyCredit\Service\CustomerService;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;

class PaymentRoute extends AbstractPaymentRoute
{
    private ContextSwitchRoute $contextSwitchRoute;

    private Storage $storage;

    private PaymentHelper $paymentHelper;

    private QuoteHelper $quoteHelper;

    private CartService $cartService;

    private CheckoutService $checkoutService;

    private CustomerService $customerService;

    private IntegrationFactory $integrationFactory;

    public function __construct(
        ContextSwitchRoute $contextSwitchRoute,
        Storage $storage,
        PaymentHelper $paymentHelper,
        QuoteHelper $quoteHelper,
        CheckoutService $checkoutService,
        CustomerService $customerService,
        CartService $cartService,
        IntegrationFactory $integrationFactory
    ) {
        $this->contextSwitchRoute = $contextSwitchRoute;
        $this->storage = $storage;
        $this->paymentHelper = $paymentHelper;
        $this->quoteHelper = $quoteHelper;
        $this->checkoutService = $checkoutService;
        $this->customerService = $customerService;
        $this->cartService = $cartService;
        $this->integrationFactory = $integrationFactory;
    }

    public function getDecorated(): AbstractPaymentRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /*
    curl -H "Content-Type: application/json" \
      -H 'sw-context-token: ..' \
      -H 'sw-access-key: ..' \
      -d '{"paymentType":"INSTALLMENT_PAYMENT", "returnUrl": "https://google.de"}' \
      $BASE_URL/store-api/easycredit/init-payment
    */

    // #[Route(path: '/store-api/easycredit/init-payment', name: 'store-api.easycredit.init-payment', methods: ['POST'])]
    // see routes.xml
    public function initPayment(Request $request, SalesChannelContext $salesChannelContext): PaymentRouteResponse
    {
        $params = \array_merge($request->query->all(), $request->request->all());

        if (isset($params['easycredit'])) {
            $params = $params['easycredit'];
        }

        if (!isset($params['paymentType'])) {
            throw new \Exception('paymentType must be set, available: ' . \print_r([...$request->query->all(), ...$request->request->all()], true));
        }

        $this->storage
            ->set('duration', isset($params['numberOfInstallments']) ? (string) $params['numberOfInstallments'] : null)
            ->set('express', $params['express'] ?? false);

        $paymentMethod = $this->paymentHelper->getPaymentMethodByPaymentType($params['paymentType'], $salesChannelContext->getContext());
        $this->updatePaymentMethod($paymentMethod, $salesChannelContext);

        $cart = $this->quoteHelper->getUncachedCart($salesChannelContext);
        $quote = $this->quoteHelper->getQuote($salesChannelContext, $cart);
        $this->storage->set('cartToken', $cart->getToken());

        if (isset($params['returnUrl'])) {
            $quote->getRedirectLinks()->setUrlSuccess($params['returnUrl'])
                ->setUrlCancellation($params['returnUrl'])
                ->setUrlDenial($params['returnUrl']);
        }

        $this->checkoutService->startCheckout($salesChannelContext, $quote);

        return new PaymentRouteResponse(new ArrayStruct([
            'redirectUrl' => $this->storage->get('redirect_url'),
            'error' => $this->storage->get('error')
        ]));
    }

    protected function updatePaymentMethod($paymentMethod, $salesChannelContext)
    {
        // will be effective after redirect (or reload of context specifically)
        $this->contextSwitchRoute->switchContext(new RequestDataBag([
            SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethod->get('id')
        ]), $salesChannelContext);

        $salesChannelContext->assign([
            'paymentMethod' => $paymentMethod
        ]);
    }

    /*
    curl -X POST \
      -H "Content-Type: application/json" \
      -H 'sw-context-token: ...' \
      -H 'sw-access-key: ...' \
      $BASE_URL/store-api/easycredit/return
    */

    // #[Route(path: '/store-api/easycredit/return', name: 'store-api.easycredit.return', methods: ['POST'])]
    // see routes.xml
    public function returnFromPaymentPage(Request $request, SalesChannelContext $salesChannelContext): PaymentRouteResponse
    {
        $checkout = $this->integrationFactory->createCheckout($salesChannelContext);

        if (!$checkout->isInitialized()) {
            throw new \Exception(
                'Payment was not initialized.'
            );
        }

        $transaction = $checkout->loadTransaction();

        if ($this->storage->get('express')) {
            $newContext = $this->customerService->handleExpress($transaction, $salesChannelContext);

            $this->storage
                ->set('express', false);

            $cart = $this->cartService->getCart($newContext->getToken(), $newContext);
            $this->storage->set('cartToken', $cart->getToken());
            $checkout->finalizeExpress($this->quoteHelper->getQuote($newContext, $cart));
        }

        $paymentMethod = $this->paymentHelper->getPaymentMethodByPaymentType(
            $transaction->getTransaction()->getPaymentType(),
            $salesChannelContext->getContext()
        );

        $this->storage->set('payment_type', $transaction->getTransaction()->getPaymentType());
        $this->updatePaymentMethod($paymentMethod, $salesChannelContext);

        $this->storage->persist();

        return new PaymentRouteResponse(new ArrayStruct([
            'summary' => $this->storage->get('summary'),
        ]));
    }
}
