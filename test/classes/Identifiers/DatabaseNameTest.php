<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Identifiers;

use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidDatabaseName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function str_repeat;

#[CoversClass(DatabaseName::class)]
#[CoversClass(InvalidDatabaseName::class)]
class DatabaseNameTest extends TestCase
{
    #[DataProvider('providerForTestValidNames')]
    public function testValidName(string $validName): void
    {
        $name = DatabaseName::from($validName);
        $this->assertEquals($validName, $name->getName());
        $this->assertEquals($validName, (string) $name);
    }

    #[DataProvider('providerForTestValidNames')]
    public function testTryFromValueWithValidName(string $validName): void
    {
        $name = DatabaseName::tryFrom($validName);
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
        $this->assertNull(DatabaseName::tryFrom($name));
        $this->expectException(InvalidDatabaseName::class);
        $this->expectExceptionMessage($exceptionMessage);
        DatabaseName::from($name);
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
