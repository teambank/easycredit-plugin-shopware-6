<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Listener;

use Shopware\Core\Content\Cookie\Event\CookieGroupCollectEvent;
use Shopware\Core\Content\Cookie\Service\CookieProvider;
use Shopware\Core\Content\Cookie\Struct\CookieEntry;
use Shopware\Core\Content\Cookie\Struct\CookieEntryCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CookieListener implements EventSubscriberInterface
{
    private const EASY_CREDIT_COMPONENTS_COOKIE_KEY = 'easycredit-components';

    public static function getSubscribedEvents(): array
    {
        return [
            CookieGroupCollectEvent::class => '__invoke',
        ];
    }

    public function __invoke(CookieGroupCollectEvent $event): void
    {
        $cookieGroup = $event->cookieGroupCollection->get(
            CookieProvider::SNIPPET_NAME_COOKIE_GROUP_REQUIRED
        );

        if (!$cookieGroup) {
            return;
        }

        $entries = $cookieGroup->getEntries();
        if ($entries === null) {
            $entries = new CookieEntryCollection();
            $cookieGroup->setEntries($entries);
        }

        // Prevent duplicates when the listener is registered multiple times.
        if ($entries->has(self::EASY_CREDIT_COMPONENTS_COOKIE_KEY)) {
            return;
        }

        $cookieEntry = new CookieEntry(self::EASY_CREDIT_COMPONENTS_COOKIE_KEY);
        $cookieEntry->name = 'cookie.easycreditComponents';
        $cookieEntry->value = '1';
        $cookieEntry->expiration = 30;

        $entries->add($cookieEntry);
    }
}
