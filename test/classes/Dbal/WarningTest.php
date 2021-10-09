<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use InvalidArgumentException;
use PhpMyAdmin\Dbal\Warning;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\Dbal\Warning
 */
class WarningTest extends TestCase
{
    public function testValidWarning(): void
    {
        $warning = Warning::fromArray(['Level' => 'Error', 'Code' => '1046', 'Message' => 'No database selected']);
        $this->assertSame('Error', $warning->level);
        $this->assertSame(1046, $warning->code);
        $this->assertSame('No database selected', $warning->message);
        $this->assertSame('Error: #1046 No database selected', (string) $warning);
    }

    /**
     * @param mixed[] $row
     *
     * @dataProvider providerForTestInvalidWarning
     */
    public function testInvalidWarning(array $row, string $exceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);
        Warning::fromArray($row);
    }

    /**
     * @return mixed[][][]|string[][]
     * @psalm-return array{array{mixed[], string}}
     */
    public function providerForTestInvalidWarning(): array
    {
        return [
            [['Code' => '1046', 'Message' => ''], 'Expected the key "Level" to exist.'],
            [['Level' => 'Error', 'Message' => ''], 'Expected the key "Code" to exist.'],
            [['Level' => 'Error', 'Code' => '1046'], 'Expected the key "Message" to exist.'],
            [
                ['Level' => '', 'Code' => '1046', 'Message' => 'No database selected'],
                'Expected a different value than "".',
            ],
            [['Level' => null, 'Code' => '1046', 'Message' => 'No database selected'], 'Expected a string. Got: NULL'],
            [
                ['Level' => 'Error', 'Code' => 'Code', 'Message' => 'No database selected'],
                'Expected a numeric. Got: string',
            ],
            [['Level' => 'Error', 'Code' => '1046', 'Message' => null], 'Expected a string. Got: NULL'],
        ];
    }
}
