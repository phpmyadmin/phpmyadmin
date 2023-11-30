<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Identifiers;

use PhpMyAdmin\Identifiers\InvalidTableName;
use PhpMyAdmin\Identifiers\TableName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function str_repeat;

#[CoversClass(TableName::class)]
#[CoversClass(InvalidTableName::class)]
class TableNameTest extends TestCase
{
    #[DataProvider('providerForTestValidNames')]
    public function testValidName(string $validName): void
    {
        $name = TableName::from($validName);
        $this->assertEquals($validName, $name->getName());
        $this->assertEquals($validName, (string) $name);
    }

    #[DataProvider('providerForTestValidNames')]
    public function testTryFromValueValidName(string $validName): void
    {
        $name = TableName::tryFrom($validName);
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

    #[DataProvider('providerForTestInvalidNames')]
    public function testInvalidNames(mixed $name, string $exceptionMessage): void
    {
        $this->assertNull(TableName::tryFrom($name));
        $this->expectException(InvalidTableName::class);
        $this->expectExceptionMessage($exceptionMessage);
        TableName::from($name);
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
