<?php

declare(strict_types=1);

namespace PhpMyAdmin\Triggers;

use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Identifiers\TriggerName;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Util;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final class Trigger
{
    public function __construct(
        public readonly TriggerName $name,
        public readonly Timing $timing,
        public readonly Event $event,
        public readonly TableName $table,
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
            $name = TriggerName::tryFrom($name);
            Assert::notNull($name);
            Assert::string($timing);
            $timing = Timing::tryFrom($timing);
            Assert::notNull($timing);
            Assert::string($event);
            $event = Event::tryFrom($event);
            Assert::notNull($event);
            Assert::string($table);
            $table = TableName::tryFrom($table);
            Assert::notNull($table);
            Assert::string($statement);
            Assert::string($definer);

            return new self($name, $timing, $event, $table, $statement, $definer);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function getDropSql(): string
    {
        return 'DROP TRIGGER IF EXISTS ' . Util::backquote($this->name);
    }

    public function getCreateSql(string $delimiter = '//'): string
    {
        return QueryGenerator::getCreateTrigger($this, $delimiter);
    }
}
