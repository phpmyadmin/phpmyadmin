<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use PhpMyAdmin\Dbal\Warning;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\Dbal\Warning
 */
class WarningTest extends TestCase
{
    /**
     * @param mixed[] $row
     *
     * @dataProvider providerForTestWarning
     */
    public function testWarning(array $row, string $level, int $code, string $message, string $toString): void
    {
        $warning = Warning::fromArray($row);
        $this->assertSame($level, $warning->level);
        $this->assertSame($code, $warning->code);
        $this->assertSame($message, $warning->message);
        $this->assertSame($toString, (string) $warning);
    }

    /**
     * @return int[][]|string[][]|string[][][]
     * @psalm-return array{string[], string, int, string, string}[]
     */
    public function providerForTestWarning(): array
    {
        return [
            [
                ['Level' => 'Error', 'Code' => '1046', 'Message' => 'No database selected'],
                'Error',
                1046,
                'No database selected',
                'Error: #1046 No database selected',
            ],
            [
                ['Level' => 'Warning', 'Code' => '0', 'Message' => ''],
                'Warning',
                0,
                '',
                'Warning: #0',
            ],
            [
                ['Level' => 'Note', 'Code' => '1', 'Message' => 'Message'],
                'Note',
                1,
                'Message',
                'Note: #1 Message',
            ],
            [
                ['Level' => 'Invalid', 'Code' => 'Invalid', 'Message' => 'Invalid'],
                '?',
                0,
                'Invalid',
                '?: #0 Invalid',
            ],
            [
                ['Level' => 'Unknown', 'Code' => '-1', 'Message' => ''],
                '?',
                0,
                '',
                '?: #0',
            ],
            [
                [],
                '?',
                0,
                '',
                '?: #0',
            ],
        ];
    }
}
