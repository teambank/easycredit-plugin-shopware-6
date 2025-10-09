<?php declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Cart;

use Shopware\Core\Checkout\Cart\Error\Error;

class ValidationError extends Error
{
    private const KEY = 'EASYCREDIT_TRANSATION_NOT_APRROVED';

    protected $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getId(): string
    {
        return \sprintf('%s-%s', $this->getMessageKey(), \md5($this->message));
    }

    public function getParameters(): array
    {
        return ['message' => $this->message];
    }

    public function getMessageKey(): string
    {
        return self::KEY;
    }

    public function getLevel(): int
    {
        return static::LEVEL_NOTICE;
    }

    public function blockOrder(): bool
    {
        return true;
    }

    public function blockResubmit(): bool
    {
        return true; // Allow resubmission after validation errors
    }
} 