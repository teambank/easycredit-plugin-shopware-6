<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Controller;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Context;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannel\ContextSwitchRoute;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;;

use Shopware\Core\Framework\Validation\DataBag\DataBag;

use Teambank\EasyCreditApiV3\Model\TransactionInformation;
use Netzkollektiv\EasyCredit\Helper\Payment as PaymentHelper;
use Netzkollektiv\EasyCredit\EasyCreditRatenkauf;
use Netzkollektiv\EasyCredit\Payment\StateHandler;
use Netzkollektiv\EasyCredit\Api\IntegrationFactory;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\Webhook\OrderTransactionNotFoundException;
use Netzkollektiv\EasyCredit\Service\CustomerService;
use Netzkollektiv\EasyCredit\Helper\Quote as QuoteHelper;
use Netzkollektiv\EasyCredit\Payment\Handler\InstallmentPaymentHandler as HandlerInstallmentPaymentHandler;
use Netzkollektiv\EasyCredit\Service\CheckoutService;

class PaymentController extends StorefrontController
{
    private IntegrationFactory $integrationFactory;

    private CartService $cartService;

    private QuoteHelper $quoteHelper;

    private StateHandler $stateHandler;

    private Storage $storage;

    private CustomerService $customerService;

    private PaymentHelper $paymentHelper;

    private CheckoutService $checkoutService;

    private ContextSwitchRoute $contextSwitchRoute;

    private EntityRepository $orderTransactionRepository;

    public function __construct(
        IntegrationFactory $integrationFactory,
        CartService $cartService,
        QuoteHelper $quoteHelper,
        StateHandler $stateHandler,
        Storage $storage,
        PaymentHelper $paymentHelper,
        CustomerService $customerService,
        CheckoutService $checkoutService,
        ContextSwitchRoute $contextSwitchRoute,
        EntityRepository $orderTransactionRepository
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->cartService = $cartService;
        $this->quoteHelper = $quoteHelper;
        $this->stateHandler = $stateHandler;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->storage = $storage;
        $this->paymentHelper = $paymentHelper;
        $this->checkoutService = $checkoutService;
        $this->customerService = $customerService;
        $this->contextSwitchRoute = $contextSwitchRoute;
    }

    public function cancel(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }

    public function express(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $params = $request->get('easycredit');

        $this->storage
            ->set('contextToken', $salesChannelContext->getToken())
            ->set('duration', $params['numberOfInstallments'] ?? null)
            ->set('express', true);

        try {
            $paymentMethod = $this->paymentHelper->getPaymentMethodByPaymentType($params['paymentType'], $salesChannelContext->getContext());

            $this->updatePaymentMethod($paymentMethod, $salesChannelContext);

            $salesChannelContext->assign([
                'paymentMethod' => $paymentMethod
            ]);

            $this->checkoutService->startCheckout($salesChannelContext);
        } catch (ConstraintViolationException $violations) {
            $errors = [];
            foreach ($violations->getViolations() as $violation) {
                $errors[] = $violation->getMessage();
            }
            $this->storage->set('error', \implode(',', $errors));
        }

        if ($this->storage->get('error')) {
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }
        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }

    public function returnAction(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        try {
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
                    ->set('contextToken', $salesChannelContext->getToken())
                    ->set('express', false);

                $cart = $this->cartService->getCart($newContext->getToken(), $newContext);
                $this->storage->set('cartToken', $cart->getToken());
                $checkout->finalizeExpress($this->quoteHelper->getQuote($cart, $newContext));
            }

            $paymentMethod = $this->paymentHelper->getPaymentMethodByPaymentType(
                $transaction->getTransaction()->getPaymentType(),
                $salesChannelContext->getContext()
            );

            $this->updatePaymentMethod($paymentMethod, $salesChannelContext);

            return $this->redirectToRoute('frontend.checkout.confirm.page');
        } catch (\Throwable $e) {
            $this->storage->set('error', $e->getMessage());
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }
    }

    protected function updatePaymentMethod($paymentMethod, $salesChannelContext)
    {
        // will be effective after redirect (or reload of context specifically)
        $this->contextSwitchRoute->switchContext(new RequestDataBag([
            SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethod->get('id')
        ]), $salesChannelContext);
    }

    public function reject(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }
}
