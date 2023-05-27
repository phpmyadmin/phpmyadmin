<?php

declare(strict_types=1);

namespace PhpMyAdmin\Triggers;

use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final class Trigger
{
    public function __construct(
        public readonly string $name,
        public readonly string $timing,
        public readonly string $event,
        public readonly string $table,
        public readonly string $statement,
        public readonly string $definer,
    ) {
    }

    /** @param mixed[] $trigger */
    public static function tryFromArray(array $trigger): self|null
    {
        try {
            $name = $trigger['Trigger'] ?? $trigger['TRIGGER_NAME'] ?? null;
            $timing = $trigger['Timing'] ?? $trigger['ACTION_TIMING'] ?? null;
            $event = $trigger['Event'] ?? $trigger['EVENT_MANIPULATION'] ?? null;
            $table = $trigger['Table'] ?? $trigger['EVENT_OBJECT_TABLE'] ?? null;
            $statement = $trigger['Statement'] ?? $trigger['ACTION_STATEMENT'] ?? null;
            $definer = $trigger['Definer'] ?? $trigger['DEFINER'] ?? null;
            Assert::string($name);
            Assert::string($timing);
            Assert::string($event);
            Assert::string($table);
            Assert::string($statement);
            Assert::string($definer);

            return new self($name, $timing, $event, $table, $statement, $definer);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
