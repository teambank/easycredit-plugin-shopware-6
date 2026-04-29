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
        // Allows using the Shopware cookie system even if services.php is not loaded.
        return [
            CookieGroupCollectEvent::class => '__invoke',
        ];
    }

    public function __invoke(CookieGroupCollectEvent $event): void
    {
        $requiredCookieGroup = $event->cookieGroupCollection->get(
            CookieProvider::SNIPPET_NAME_COOKIE_GROUP_REQUIRED
        );

        if (!$requiredCookieGroup) {
            return;
        }

        $entries = $requiredCookieGroup->getEntries();
        if ($entries === null) {
            $entries = new CookieEntryCollection();
            $requiredCookieGroup->setEntries($entries);
        }

        // Prevent duplicates when the listener is registered multiple times.
        if ($entries->has(self::EASY_CREDIT_COMPONENTS_COOKIE_KEY)) {
            return;
        }

        $cookieEntry = new CookieEntry(self::EASY_CREDIT_COMPONENTS_COOKIE_KEY);
        $cookieEntry->name = 'cookie.easycreditComponents';
        $cookieEntry->description = 'cookie.easycreditComponentsDescription';
        $cookieEntry->value = '1';
        $cookieEntry->expiration = 30;

        $entries->add($cookieEntry);
    }
}
