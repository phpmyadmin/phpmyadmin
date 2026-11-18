<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use PhpMyAdmin\Dbal\DatabaseName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\InvalidArgumentException;

use function str_repeat;

/**
 * @covers \PhpMyAdmin\Dbal\DatabaseName
 */
#[CoversClass(DatabaseName::class)]
class DatabaseNameTest extends TestCase
{
    public function testEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The database name must be a non-empty string.');
        DatabaseName::fromValue('');
    }

    public function testNameWithTrailingWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The database name cannot end with a space character.');
        DatabaseName::fromValue('a ');
    }

    public function testLongName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The database name cannot be longer than 64 characters.');
        DatabaseName::fromValue(str_repeat('a', 65));
    }

    public function testValidName(): void
    {
        $name = DatabaseName::fromValue('name');
        self::assertSame('name', $name->getName());
        self::assertSame('name', (string) $name);
    }

    /**
     * @param mixed $name
     *
     * @dataProvider providerForTestInvalidMixedNames
     */
    #[DataProvider('providerForTestInvalidMixedNames')]
    public function testInvalidMixedNames($name, string $exceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        DatabaseName::fromValue($name);
    }

    /**
     * @return mixed[][]
     * @psalm-return non-empty-list<array{mixed, string}>
     */
    public static function providerForTestInvalidMixedNames(): array
    {
        return [
            [null, 'The database name must be a non-empty string.'],
            [1, 'The database name must be a non-empty string.'],
            [['db'], 'The database name must be a non-empty string.'],
        ];
    }
}
