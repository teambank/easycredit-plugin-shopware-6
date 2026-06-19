<?php declare(strict_types=1);

namespace Netzkollektiv\EasyCredit\Test\Unit\Rule;

use Netzkollektiv\EasyCredit\Rule\CartPositionPriceRule;
use Netzkollektiv\EasyCredit\Test\Helper\CartRuleScopeFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Shopware\Core\Framework\Rule\Rule;

class CartPositionPriceRuleTest extends TestCase
{
    #[DataProvider('operatorProvider')]
    public function testMatchUsesPositionPriceWithoutInterest(
        string $operator,
        float $threshold,
        float $totalPrice,
        float $positionPrice,
        ?float $interest,
        bool $expected
    ): void {
        $rule = new CartPositionPriceRule($operator, $threshold);
        $scope = CartRuleScopeFactory::create($totalPrice, $positionPrice, $interest);

        self::assertSame($expected, $rule->match($scope));
    }

    /**
     * @return iterable<string, array{string, float, float, float, ?float, bool}>
     */
    public static function operatorProvider(): iterable
    {
        yield 'gte matches position price' => [Rule::OPERATOR_GTE, 100.0, 150.0, 120.0, 10.0, true];
        yield 'gte excludes interest' => [Rule::OPERATOR_GTE, 100.0, 150.0, 110.0, 20.0, false];
        yield 'lte matches' => [Rule::OPERATOR_LTE, 200.0, 150.0, 150.0, null, true];
        yield 'eq matches' => [Rule::OPERATOR_EQ, 140.0, 150.0, 150.0, 10.0, true];
    }

    public function testMatchReturnsFalseForNonCartScope(): void
    {
        $rule = new CartPositionPriceRule(Rule::OPERATOR_GTE, 100.0);

        self::assertFalse($rule->match(CartRuleScopeFactory::createNonCartScope()));
    }

    public function testMatchThrowsForUnsupportedOperator(): void
    {
        $rule = new CartPositionPriceRule('invalid', 100.0);

        $this->expectException(UnsupportedOperatorException::class);

        $rule->match(CartRuleScopeFactory::create(150.0, 150.0));
    }
}
