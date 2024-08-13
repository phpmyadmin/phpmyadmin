<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use PhpMyAdmin\Dbal\TableName;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

use function str_repeat;

/**
 * @covers \PhpMyAdmin\Dbal\TableName
 */
class TableNameTest extends TestCase
{
    public function testEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a different value than "".');
        TableName::fromValue('');
    }

    public function testNameWithTrailingWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value not to end with " ". Got: "a "');
        TableName::fromValue('a ');
    }

    public function testLongName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected a value to contain at most 64 characters. Got: '
            . '"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"'
        );
        TableName::fromValue(str_repeat('a', 65));
    }

    public function testValidName(): void
    {
        $name = TableName::fromValue('name');
        $this->assertEquals('name', $name->getName());
        $this->assertEquals('name', (string) $name);
    }

    /**
     * @param mixed $name
     *
     * @dataProvider providerForTestInvalidMixedNames
     */
    public function testInvalidMixedNames($name, string $exceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        TableName::fromValue($name);
    }

    /**
     * @return mixed[][]
     * @psalm-return non-empty-list<array{mixed, string}>
     */
    public static function providerForTestInvalidMixedNames(): array
    {
        return [
            [null, 'Expected a string. Got: NULL'],
            [1, 'Expected a string. Got: integer'],
            [['table'], 'Expected a string. Got: array'],
        ];
    }
}
