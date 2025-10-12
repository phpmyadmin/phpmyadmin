<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table\Maintenance;

use PhpMyAdmin\Table\Maintenance\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Message::class)]
class MessageTest extends TestCase
{
    /** @param array<string, string|bool> $row */
    #[DataProvider('providerForTestFromArray')]
    public function testFromArray(array $row, string $table, string $operation, string $type, string $text): void
    {
        $message = Message::fromArray($row);
        self::assertSame($message->table, $table);
        self::assertSame($message->operation, $operation);
        self::assertSame($message->type, $type);
        self::assertSame($message->text, $text);
    }

    /** @return array{array<string, string|false>, string, string, string, string}[] */
    public static function providerForTestFromArray(): array
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
