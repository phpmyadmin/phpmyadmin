<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tracking;

use PhpMyAdmin\ConfigStorage\Features\TrackingFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Util;

use function array_column;
use function array_diff;
use function array_values;
use function sprintf;

class TrackingChecker
{
    private TrackingFeature|null $trackingFeature;

    public function __construct(
        private DatabaseInterface $dbi,
        Relation $relation,
    ) {
        $this->trackingFeature = $relation->getRelationParameters()->trackingFeature;
    }

    /**
     * Get a list of untracked tables.
     * Deactivated tracked tables are not included in the list.
     *
     * @return array<int, string|TableName>
     */
    public function getUntrackedTableNames(string $dbName): array
    {
        $tableList = $this->dbi->getTables($dbName);

        if ($this->trackingFeature === null) {
            return $tableList;
        }

        $trackedTables = array_column($this->getTrackedTables($dbName), 'name');

        return array_values(array_diff($tableList, $trackedTables));
    }

    /** @return TrackedTable[] */
    public function getTrackedTables(string $dbName): array
    {
        if (! Tracker::isEnabled()) {
            return [];
        }

        if ($this->trackingFeature === null) {
            return [];
        }

        $sqlQuery = sprintf(
            "SELECT table_name, tracking_active
            FROM (
                SELECT table_name, MAX(version) version
                FROM %s.%s WHERE db_name = %s AND table_name <> ''
                GROUP BY table_name
            ) filtered_tables
            JOIN %s.%s USING(table_name, version)",
            Util::backquote($this->trackingFeature->database),
            Util::backquote($this->trackingFeature->tracking),
            $this->dbi->quoteString($dbName, ConnectionType::ControlUser),
            Util::backquote($this->trackingFeature->database),
            Util::backquote($this->trackingFeature->tracking),
        );

        $trackedTables = [];
        foreach ($this->dbi->queryAsControlUser($sqlQuery) as $row) {
            $trackedTable = new TrackedTable(TableName::from($row['table_name']), (bool) $row['tracking_active']);
            $trackedTables[$trackedTable->name->getName()] = $trackedTable;
        }

        return $trackedTables;
    }
}
