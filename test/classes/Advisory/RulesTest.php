<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Advisory;

use PhpMyAdmin\Advisory\Rules;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\Advisory\Rules
 * @psalm-import-type RuleType from Rules
 */
class RulesTest extends TestCase
{
    /**
     * @psalm-param callable(): list<RuleType> $rulesFactory
     *
     * @dataProvider providerForTestRules
     */
    public function testRules(callable $rulesFactory): void
    {
        $rules = $rulesFactory();
        $this->assertNotEmpty($rules);
        foreach ($rules as $rule) {
            $this->assertArrayHasKey('id', $rule);
            $this->assertArrayHasKey('name', $rule);
            $this->assertArrayHasKey('formula', $rule);
            $this->assertArrayHasKey('test', $rule);
            $this->assertArrayHasKey('issue', $rule);
            $this->assertArrayHasKey('recommendation', $rule);
            $this->assertArrayHasKey('justification', $rule);
            $this->assertContainsOnly('string', $rule);
        }
    }

    /**
     * @return array<string, callable[]>
     * @psalm-return array<string, array{callable(): list<RuleType>}>
     */
    public function providerForTestRules(): iterable
    {
        return [
            'generic rules' => [
                static function (): array {
                    return Rules::getGeneric();
                },
            ],
            'rules before MySQL 8.0.3' => [
                static function (): array {
                    return Rules::getBeforeMySql80003();
                },
            ],
        ];
    }
}
