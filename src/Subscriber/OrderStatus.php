<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Subscriber;

use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;
use Netzkollektiv\EasyCredit\Service\TransactionService;
use Netzkollektiv\EasyCredit\Compatibility\Capabilities;
use Teambank\EasyCreditApiV3\Model\CaptureRequest;
use Teambank\EasyCreditApiV3\Model\RefundRequest;
use Teambank\EasyCreditApiV3\Model\TransactionResponse;

class OrderStatus implements EventSubscriberInterface
{
    private SettingsServiceInterface $settings;

    private TransactionService $transactionService;

    private Capabilities $caps;

    public function __construct(
        SettingsServiceInterface $settingsService,
        TransactionService $transactionService,
        Capabilities $caps
    ) {
        $this->settings = $settingsService;
        $this->transactionService = $transactionService;
        $this->caps = $caps;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_enter.order_delivery.state.shipped' => 'onOrderShipped',
            'state_enter.order_delivery.state.returned' => 'onOrderReturned',
        ];
    }

    public function onOrderShipped(OrderStateMachineStateChangeEvent $event): void
    {
        if ($this->caps->hasFlowBuilder()) {
            return;
        }

        $markShipped = $this->settings
            ->getSettings($event->getSalesChannelId(), false)
            ->getMarkShipped();

        if (!$markShipped) {
            return;
        }

        $this->transactionService->captureTransaction($event);
    }

    public function onOrderReturned(OrderStateMachineStateChangeEvent $event): void
    {
        if ($this->caps->hasFlowBuilder()) {
            return;
        }

        $markRefunded = $this->settings
            ->getSettings($event->getSalesChannelId(), false)
            ->getMarkRefunded();
            
        if (!$markRefunded) {
            return;
        }
        
        $this->transactionService->refundTransaction($event);
    }
}
