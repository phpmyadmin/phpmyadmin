<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use InvalidArgumentException;
use PhpMyAdmin\Dbal\DatabaseName;
use PHPUnit\Framework\TestCase;

use function str_repeat;

/**
 * @covers \PhpMyAdmin\Dbal\DatabaseName
 */
class DatabaseNameTest extends TestCase
{
    public function testEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a different value than "".');
        DatabaseName::fromString('');
    }

    public function testNameWithTrailingWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value not to end with " ". Got: "a "');
        DatabaseName::fromString('a ');
    }

    public function testLongName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected a value to contain at most 64 characters. Got: '
            . '"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"'
        );
        DatabaseName::fromString(str_repeat('a', 65));
    }

    public function testValidName(): void
    {
        $name = DatabaseName::fromString('name');
        $this->assertEquals('name', $name->getName());
        $this->assertEquals('name', (string) $name);
    }
}
