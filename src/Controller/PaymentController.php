<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;;

use Netzkollektiv\EasyCredit\Api\Storage;
use Netzkollektiv\EasyCredit\Payment\PaymentRoute;

class PaymentController extends StorefrontController
{
    private Storage $storage;

    private PaymentRoute $paymentRoute;

    public function __construct(
        Storage $storage,
        PaymentRoute $paymentRoute
    ) {
        $this->storage = $storage;
        $this->paymentRoute = $paymentRoute;
    }

    public function cancel(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }

    public function express(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        try {
            $request->query->set('express', true);
            $this->paymentRoute->initPayment($request, $salesChannelContext);
        } catch (ConstraintViolationException $violations) {
            $errors = [];
            foreach ($violations->getViolations() as $violation) {
                $errors[] = $violation->getMessage();
            }
            $this->storage->set('express', false)->set('error', \implode(',', $errors))->persist();
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
            $this->storage->set('error', $e->getMessage())->persist();
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }
    }

    public function reject(SalesChannelContext $salesChannelContext): RedirectResponse
    {
        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }
}
