<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table\Maintenance;

use function is_string;

/** @psalm-immutable */
final class Message
{
    private function __construct(
        public string $table,
        public string $operation,
        public string $type,
        public string $text,
    ) {
    }

    /** @param mixed[] $row */
    public static function fromArray(array $row): self
    {
        $table = isset($row['Table']) && is_string($row['Table']) ? $row['Table'] : '';
        $operation = isset($row['Op']) && is_string($row['Op']) ? $row['Op'] : '';
        $type = isset($row['Msg_type']) && is_string($row['Msg_type']) ? $row['Msg_type'] : '';
        $text = isset($row['Msg_text']) && is_string($row['Msg_text']) ? $row['Msg_text'] : '';

        return new self($table, $operation, $type, $text);
    }
}
