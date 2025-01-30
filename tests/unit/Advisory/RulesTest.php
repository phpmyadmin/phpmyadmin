<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Advisory;

use PhpMyAdmin\Advisory\Rules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** @psalm-import-type RuleType from Rules */
#[CoversClass(Rules::class)]
class RulesTest extends TestCase
{
    /** @psalm-param callable(): list<RuleType> $rulesFactory */
    #[DataProvider('providerForTestRules')]
    public function testRules(callable $rulesFactory): void
    {
        $rules = $rulesFactory();
        self::assertNotEmpty($rules);
        foreach ($rules as $rule) {
            self::assertArrayHasKey('id', $rule);
            self::assertArrayHasKey('name', $rule);
            self::assertArrayHasKey('formula', $rule);
            self::assertArrayHasKey('test', $rule);
            self::assertArrayHasKey('issue', $rule);
            self::assertArrayHasKey('recommendation', $rule);
            self::assertArrayHasKey('justification', $rule);
            self::assertContainsOnlyString($rule);
        }
    }

    /**
     * @return array<string, callable[]>
     * @psalm-return array<string, array{callable(): list<RuleType>}>
     */
    public static function providerForTestRules(): iterable
    {
        return [
            'generic rules' => [
                Rules::getGeneric(...),
            ],
            'rules before MySQL 8.0.3' => [
                Rules::getBeforeMySql80003(...),
            ],
        ];
    }
}
