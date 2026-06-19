<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Test\Unit\Compatibility;

use Netzkollektiv\EasyCredit\Compatibility\Capabilities;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CapabilitiesTest extends TestCase
{
    #[DataProvider('flowBuilderVersionProvider')]
    public function testHasFlowBuilder(string $version, bool $expected): void
    {
        $capabilities = new Capabilities($version);

        self::assertSame($expected, $capabilities->hasFlowBuilder());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function flowBuilderVersionProvider(): iterable
    {
        yield 'before flow builder' => ['6.4.5.0', false];
        yield 'flow builder introduced' => ['6.4.6.0', true];
        yield 'newer release' => ['6.7.10.0', true];
    }
}
