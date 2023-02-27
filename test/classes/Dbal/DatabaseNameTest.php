<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\InvalidDatabaseName;
use PHPUnit\Framework\TestCase;

use function str_repeat;

/**
 * @covers \PhpMyAdmin\Dbal\DatabaseName
 * @covers \PhpMyAdmin\Dbal\InvalidDatabaseName
 */
class DatabaseNameTest extends TestCase
{
    /** @dataProvider providerForTestValidNames */
    public function testValidName(string $validName): void
    {
        $name = DatabaseName::fromValue($validName);
        $this->assertEquals($validName, $name->getName());
        $this->assertEquals($validName, (string) $name);
    }

    /** @dataProvider providerForTestValidNames */
    public function testTryFromValueWithValidName(string $validName): void
    {
        $name = DatabaseName::tryFromValue($validName);
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
        $this->assertNull(DatabaseName::tryFromValue($name));
        $this->expectException(InvalidDatabaseName::class);
        $this->expectExceptionMessage($exceptionMessage);
        DatabaseName::fromValue($name);
    }

    /**
     * @return iterable<string, mixed[]>
     * @psalm-return iterable<string, array{mixed, non-empty-string}>
     */
    public static function providerForTestInvalidNames(): iterable
    {
        yield 'null' => [null, 'The database name must be a non-empty string.'];
        yield 'integer' => [1, 'The database name must be a non-empty string.'];
        yield 'array' => [['database'], 'The database name must be a non-empty string.'];
        yield 'empty string' => ['', 'The database name must be a non-empty string.'];
        yield 'too long name' => [str_repeat('a', 65), 'The database name cannot be longer than 64 characters.'];
        yield 'trailing space' => ['a ', 'The database name cannot end with a space character.'];
    }
}
