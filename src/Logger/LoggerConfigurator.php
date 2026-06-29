<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Logger;

use Psr\Log\LogLevel;
use Monolog\Handler\AbstractHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Shopware\Core\PlatformRequest;
use Netzkollektiv\EasyCredit\Setting\Service\SettingsServiceInterface;

class LoggerConfigurator implements EventSubscriberInterface
{
    private SettingsServiceInterface $settings;

    protected $requestStack;

    private $handler;

    public function __construct(
        SettingsServiceInterface $settingsService,
        RequestStack $requestStack,
        AbstractHandler $handler
    ) {
        $this->settings = $settingsService;
        $this->requestStack = $requestStack;
        $this->handler = $handler;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['configureLoglevel', 0],
            ],
        ];
    }

    public function configureLoglevel(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $salesChannelId = null;
        if ($request && $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID)) {
            $salesChannelId = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID);
        }

        $this->handler->setLevel(
            $this->settings->getSettings($salesChannelId, false)->getDebug()
                ? LogLevel::DEBUG
                : LogLevel::ERROR
        );
    }
}
