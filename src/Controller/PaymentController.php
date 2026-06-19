<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\EasyCreditRatenkauf;
use Netzkollektiv\EasyCredit\Payment\PaymentRoute;

class PaymentController extends StorefrontController
{
    private Storage $storage;

    private PaymentRoute $paymentRoute;

    private LoggerInterface $logger;

    public function __construct(
        Storage $storage,
        PaymentRoute $paymentRoute,
        LoggerInterface $logger
    ) {
        $this->storage = $storage;
        $this->paymentRoute = $paymentRoute;
        $this->logger = $logger;
    }

    public function cancel(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }

    public function express(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        try {
            if (!$request->request->get('express') && !$request->query->get('express')) {
                $request->request->set('express', true);
            }
            $this->paymentRoute->initPayment($request, $salesChannelContext);
        } catch (ConstraintViolationException $violations) {
            $this->storage
                ->set('express', false)
                ->set('error', EasyCreditRatenkauf::GENERIC_STOREFRONT_ERROR_MESSAGE)
                ->persist();
        }

        if ($this->storage->get('error')) {
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }
        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }

    public function returnAction(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        try {
            $this->paymentRoute->returnFromPaymentPage($request, $salesChannelContext);
            return $this->redirectToRoute('frontend.checkout.confirm.page');
        } catch (\Throwable $e) {
            $this->logger->error('EasyCredit return failed', ['exception' => $e]);
            $this->storage->set('error', EasyCreditRatenkauf::GENERIC_STOREFRONT_ERROR_MESSAGE)->persist();
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }
    }

    public function reject(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }
}
