<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use Teambank\EasyCreditApiV3\Integration\StorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Monolog\Logger;

class Storage implements StorageInterface
{
    protected $requestStack;

    protected $logger;

    public function __construct(
        RequestStack $requestStack,
        Logger $logger
    ) {
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    public function set($key, $value): self
    {
        $this->logger->debug('storage::set '.$key.' = ('.\gettype($value).') '.$value);
        $this->requestStack->getSession()->set('easycredit_' . $key, $value);

        return $this;
    }

    public function get($key)
    {
        $value = $this->requestStack->getSession()->get('easycredit_' . $key);
        $this->logger->debug('storage::get '.$key.' = ('.\gettype($value).')'.$value);
        return $value;
    }

    public function all(): array
    {
        $session = [];
        foreach ($this->requestStack->getSession() as $key => $value) {
            if (\mb_strpos($key, 'easycredit') === 0) {
                $session[$key] = $value;
            }
        }

        return $session;
    }

    public function clear(): self
    {
        $backtrace = \debug_backtrace();
        $this->logger->info('storage::clear from ' .$backtrace[1]['class'].':'.$backtrace[1]['function']);

        foreach (\array_keys($this->all()) as $key) {
            $this->requestStack->getSession()->remove($key);
        }

        return $this;
    }
}
