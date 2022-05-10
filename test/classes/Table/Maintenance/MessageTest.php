<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table\Maintenance;

use PhpMyAdmin\Table\Maintenance\Message;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\Table\Maintenance\Message
 */
class MessageTest extends TestCase
{
    /**
     * @param mixed[] $row
     *
     * @dataProvider providerForTestFromArray
     */
    public function testFromArray(array $row, string $table, string $operation, string $type, string $text): void
    {
        $message = Message::fromArray($row);
        $this->assertSame($message->table, $table);
        $this->assertSame($message->operation, $operation);
        $this->assertSame($message->type, $type);
        $this->assertSame($message->text, $text);
    }

    /**
     * @return array<int|string, array<int, array<string, mixed>|string>>
     * @psalm-return array{mixed[], string, string, string, string}[]
     */
    public function providerForTestFromArray(): array
    {
        return [
            [[], '', '', '', ''],
            [
                ['Table' => 'sakila.actor', 'Op' => 'analyze', 'Msg_type' => 'status', 'Msg_text' => 'OK'],
                'sakila.actor',
                'analyze',
                'status',
                'OK',
            ],
            [['Table' => false, 'Op' => false, 'Msg_type' => false, 'Msg_text' => false], '', '', '', ''],
        ];
    }
}
