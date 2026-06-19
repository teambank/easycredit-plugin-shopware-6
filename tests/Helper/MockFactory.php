<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Test\Helper;

use PHPUnit\Framework\MockObject\Generator\Generator;
use PHPUnit\Framework\MockObject\MockObject;

final class MockFactory
{
    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T&MockObject
     */
    public static function create(string $className): object
    {
        $generator = new Generator();

        /** @var T&MockObject $mock */
        $mock = $generator->testDouble(
            $className,
            mockObject: true,
            markAsMockObject: true,
            callOriginalConstructor: false,
        );

        return $mock;
    }
}
