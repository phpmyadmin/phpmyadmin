<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use InvalidArgumentException;
use PhpMyAdmin\Dbal\TableName;
use PHPUnit\Framework\TestCase;

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
        new TableName('');
    }

    public function testNameWithTrailingWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value not to end with " ". Got: "a "');
        new TableName('a ');
    }

    public function testLongName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected a value to contain at most 64 characters. Got: '
            . '"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"'
        );
        new TableName(str_repeat('a', 65));
    }

    public function testValidName(): void
    {
        $name = new TableName('name');
        $this->assertEquals('name', $name->getName());
        $this->assertEquals('name', (string) $name);
    }
}
