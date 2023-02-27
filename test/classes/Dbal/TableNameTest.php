<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use PhpMyAdmin\Dbal\InvalidTableName;
use PhpMyAdmin\Dbal\TableName;
use PHPUnit\Framework\TestCase;

use function str_repeat;

/**
 * @covers \PhpMyAdmin\Dbal\TableName
 * @covers \PhpMyAdmin\Dbal\InvalidTableName
 */
class TableNameTest extends TestCase
{
    /** @dataProvider providerForTestValidNames */
    public function testValidName(string $validName): void
    {
        $name = TableName::fromValue($validName);
        $this->assertEquals($validName, $name->getName());
        $this->assertEquals($validName, (string) $name);
    }

    /** @dataProvider providerForTestValidNames */
    public function testTryFromValueValidName(string $validName): void
    {
        $name = TableName::tryFromValue($validName);
        $this->assertNotNull($name);
        $this->assertEquals($validName, $name->getName());
        $this->assertEquals($validName, (string) $name);
    }

    /** @return iterable<int, string[]> */
    public static function providerForTestValidNames(): iterable
    {
        yield ['name'];
        yield ['0'];
        yield [str_repeat('a', 64)];
    }

    /** @dataProvider providerForTestInvalidNames */
    public function testInvalidNames(mixed $name, string $exceptionMessage): void
    {
        $this->assertNull(TableName::tryFromValue($name));
        $this->expectException(InvalidTableName::class);
        $this->expectExceptionMessage($exceptionMessage);
        TableName::fromValue($name);
    }

    /**
     * @return iterable<string, mixed[]>
     * @psalm-return iterable<string, array{mixed, non-empty-string}>
     */
    public static function providerForTestInvalidNames(): iterable
    {
        yield 'null' => [null, 'The table name must be a non-empty string.'];
        yield 'integer' => [1, 'The table name must be a non-empty string.'];
        yield 'array' => [['table'], 'The table name must be a non-empty string.'];
        yield 'empty string' => ['', 'The table name must be a non-empty string.'];
        yield 'too long name' => [str_repeat('a', 65), 'The table name cannot be longer than 64 characters.'];
        yield 'trailing space' => ['a ', 'The table name cannot end with a space character.'];
    }
}
