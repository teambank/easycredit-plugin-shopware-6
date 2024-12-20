<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Subscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\Event\CartVerifyPersistEvent;

class PreventCartPersistDuringRuleEvaluation implements EventSubscriberInterface {

    public static function getSubscribedEvents(): array
    {
        return [
            CartVerifyPersistEvent::class => 'preventPersist'
        ];
    }

    public function preventPersist(CartVerifyPersistEvent $event): void {
        $prefix = 'easycredit';
        if (\mb_substr($event->getCart()->getToken(), 0, \mb_strlen($prefix)) === $prefix) {
            $event->setShouldPersist(false);
        }
    }
}