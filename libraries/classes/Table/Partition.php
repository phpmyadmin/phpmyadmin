<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Util;
use function sprintf;

final class Partition
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    public function analyze(string $db, string $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s ANALYZE PARTITION %s;',
            Util::backquote($table),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }

    public function check(string $db, string $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s CHECK PARTITION %s;',
            Util::backquote($table),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }

    public function drop(string $db, string $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s DROP PARTITION %s;',
            Util::backquote($table),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($query);

        return [(bool) $result, $query];
    }

    public function optimize(string $db, string $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s OPTIMIZE PARTITION %s;',
            Util::backquote($table),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }

    public function rebuild(string $db, string $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s REBUILD PARTITION %s;',
            Util::backquote($table),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($query);

        return [(bool) $result, $query];
    }

    public function repair(string $db, string $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s REPAIR PARTITION %s;',
            Util::backquote($table),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }

    public function truncate(string $db, string $table, string $partition): array
    {
        $query = sprintf(
            'ALTER TABLE %s TRUNCATE PARTITION %s;',
            Util::backquote($table),
            Util::backquote($partition)
        );

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($query);

        return [(bool) $result, $query];
    }
}
