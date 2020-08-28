<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Index;
use PhpMyAdmin\Util;
use function implode;
use function sprintf;

final class Maintenance
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    /**
     * @param string[] $tables
     *
     * @return array
     */
    public function getAnalyzeTableRows(string $db, array $tables): array
    {
        $backQuotedTables = Util::backquote($tables);
        $query = 'ANALYZE TABLE ' . implode(', ', $backQuotedTables) . ';';

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }

    /**
     * @param string[] $tables
     *
     * @return array
     */
    public function getCheckTableRows(string $db, array $tables): array
    {
        $backQuotedTables = Util::backquote($tables);
        $query = 'CHECK TABLE ' . implode(', ', $backQuotedTables) . ';';

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }

    /**
     * @param string[] $tables
     *
     * @return array
     */
    public function getChecksumTableRows(string $db, array $tables): array
    {
        $backQuotedTables = Util::backquote($tables);
        $query = 'CHECKSUM TABLE ' . implode(', ', $backQuotedTables) . ';';

        $this->dbi->selectDb($db);
        $rows = $this->dbi->fetchResult($query);
        $warnings = $this->dbi->getWarnings();

        return [$rows, $query, $warnings];
    }

    /** @param string[] $tables */
    public function getIndexesProblems(string $db, array $tables): string
    {
        $indexesProblems = '';

        foreach ($tables as $table) {
            $check = Index::findDuplicates($table, $db);

            if (empty($check)) {
                continue;
            }

            $indexesProblems .= sprintf(__('Problems with indexes of table `%s`'), $table);
            $indexesProblems .= $check;
        }

        return $indexesProblems;
    }

    /**
     * @param string[] $tables
     *
     * @return array
     */
    public function getOptimizeTableRows(string $db, array $tables): array
    {
        $backQuotedTables = Util::backquote($tables);
        $query = 'OPTIMIZE TABLE ' . implode(', ', $backQuotedTables) . ';';

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }

    /**
     * @param string[] $tables
     *
     * @return array
     */
    public function getRepairTableRows(string $db, array $tables): array
    {
        $backQuotedTables = Util::backquote($tables);
        $query = 'REPAIR TABLE ' . implode(', ', $backQuotedTables) . ';';

        $this->dbi->selectDb($db);
        $result = $this->dbi->fetchResult($query);

        $rows = [];
        foreach ($result as $row) {
            $rows[$row['Table']][] = $row;
        }

        return [$rows, $query];
    }
}
