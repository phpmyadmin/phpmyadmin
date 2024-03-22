<?php

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Util;

use function in_array;

class ObjectFetcher
{
    /** @var string[]|null */
    private array|null $tables = null;
    /** @var string[]|null */
    private array|null $views = null;
    /** @var string[]|null */
    private array|null $procedures = null;
    /** @var string[]|null */
    private array|null $functions = null;
    /** @var string[]|null */
    private array|null $events = null;

    public function __construct(private DatabaseInterface $dbi, private Config $config)
    {
    }

    /**
     * Returns the list of tables inside this database
     *
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return string[]
     */
    public function getTables(string $realName, string $searchClause): array
    {
        if ($this->tables === null) {
            $this->bufferTablesAndViews($realName, $searchClause);
        }

        return $this->tables;
    }

    /**
     * Returns the list of views inside this database
     *
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return string[]
     */
    public function getViews(string $realName, string $searchClause): array
    {
        if ($this->views === null) {
            $this->bufferTablesAndViews($realName, $searchClause);
        }

        return $this->views;
    }

    /**
     * Returns the list of procedures inside this database
     *
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return string[]
     */
    public function getProcedures(string $realName, string $searchClause): array
    {
        if ($this->procedures !== null) {
            return $this->procedures;
        }

        return $this->procedures = $this->getRoutines('PROCEDURE', $realName, $searchClause);
    }

    /**
     * Returns the list of functions inside this database
     *
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return string[]
     */
    public function getFunctions(string $realName, string $searchClause): array
    {
        if ($this->functions !== null) {
            return $this->functions;
        }

        return $this->functions = $this->getRoutines('FUNCTION', $realName, $searchClause);
    }

    /**
     * Returns the list of events inside this database
     *
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return string[]
     */
    public function getEvents(string $realName, string $searchClause): array
    {
        if ($this->events !== null) {
            return $this->events;
        }

        return $this->events = $this->getEventsFromDb($realName, $searchClause);
    }

    /** @return string[] */
    private function getEventsFromDb(string $realName, string $searchClause): array
    {
        if (! $this->config->selectedServer['DisableIS']) {
            $query = 'SELECT `EVENT_NAME` AS `name` ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`EVENTS` ';
            $query .= 'WHERE `EVENT_SCHEMA` ' . Util::getCollateForIS() . '=' . $this->dbi->quoteString($realName);
            if ($searchClause !== '') {
                $query .= ' AND `EVENT_NAME` LIKE ';
                $query .= $this->dbi->quoteString('%' . $this->dbi->escapeMysqlWildcards($searchClause) . '%');
            }

            $query .= ' ORDER BY `EVENT_NAME` ASC';

            return $this->dbi->fetchResult($query);
        }

        $query = 'SHOW EVENTS FROM ' . Util::backquote($realName);
        if ($searchClause !== '') {
            $query .= ' WHERE `Name` LIKE ';
            $query .= $this->dbi->quoteString('%' . $this->dbi->escapeMysqlWildcards($searchClause) . '%');
        }

        $retval = [];
        $handle = $this->dbi->tryQuery($query);
        if ($handle !== false) {
            /** @var string[] $retval */
            $retval = $handle->fetchAllColumn('Name');
        }

        return $retval;
    }

    /**
     * @psalm-assert string[] $this->tables
     * @phpstan-assert string[] $this->tables
     * @psalm-assert string[] $this->views
     * @phpstan-assert string[] $this->views
     */
    private function bufferTablesAndViews(string $realName, string $searchClause): void
    {
        $this->tables = [];
        $this->views = [];
        $tablesAndViews = $this->getTablesAndViews($realName, $searchClause);

        foreach ($tablesAndViews as $tableOrView) {
            if (in_array($tableOrView['type'], ['BASE TABLE', 'SYSTEM VERSIONED'], true)) {
                $this->tables[] = $tableOrView['name'];
            } else {
                $this->views[] = $tableOrView['name'];
            }
        }
    }

    /**
     * Returns the list of tables or views inside this database
     *
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array{name:string, type:string}[]
     */
    private function getTablesAndViews(string $realName, string $searchClause): array
    {
        if (! $this->config->selectedServer['DisableIS']) {
            $query = 'SELECT `TABLE_NAME` AS `name`, `TABLE_TYPE` AS `type` ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`TABLES` ';
            $query .= 'WHERE `TABLE_SCHEMA`=' . $this->dbi->quoteString($realName);
            if ($searchClause !== '') {
                $query .= ' AND `TABLE_NAME` LIKE ';
                $query .= $this->dbi->quoteString('%' . $this->dbi->escapeMysqlWildcards($searchClause) . '%');
            }

            $query .= ' ORDER BY `TABLE_NAME` ASC';

            return $this->dbi->fetchResult($query);
        }

        $query = 'SHOW FULL TABLES FROM ';
        $query .= Util::backquote($realName);
        if ($searchClause !== '') {
            $query .= ' WHERE ' . Util::backquote('Tables_in_' . $realName) . ' LIKE ';
            $query .= $this->dbi->quoteString('%' . $this->dbi->escapeMysqlWildcards($searchClause) . '%');
        }

        $retval = [];
        $handle = $this->dbi->tryQuery($query);
        if ($handle !== false) {
            while ($row = $handle->fetchRow()) {
                /** @var string[] $row */
                $retval[] = ['name' => $row[0], 'type' => $row[1]];
            }
        }

        return $retval;
    }

    /**
     * Returns the list of procedures or functions inside this database
     *
     * @param string $routineType  PROCEDURE|FUNCTION
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return string[]
     */
    private function getRoutines(string $routineType, string $realName, string $searchClause): array
    {
        if (! $this->config->selectedServer['DisableIS']) {
            $query = 'SELECT `ROUTINE_NAME` AS `name` ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`ROUTINES` ';
            $query .= 'WHERE `ROUTINE_SCHEMA` ' . Util::getCollateForIS() . '=' . $this->dbi->quoteString($realName);
            $query .= " AND `ROUTINE_TYPE`='" . $routineType . "'";
            if ($searchClause !== '') {
                $query .= ' AND `ROUTINE_NAME` LIKE ';
                $query .= $this->dbi->quoteString('%' . $this->dbi->escapeMysqlWildcards($searchClause) . '%');
            }

            $query .= ' ORDER BY `ROUTINE_NAME` ASC';

            return $this->dbi->fetchResult($query);
        }

        $query = 'SHOW ' . $routineType . ' STATUS WHERE `Db`=' . $this->dbi->quoteString($realName);
        if ($searchClause !== '') {
            $query .= ' AND `Name` LIKE ';
            $query .= $this->dbi->quoteString('%' . $this->dbi->escapeMysqlWildcards($searchClause) . '%');
        }

        $retval = [];
        $handle = $this->dbi->tryQuery($query);
        if ($handle !== false) {
            /** @var string[] $retval */
            $retval = $handle->fetchAllColumn('Name');
        }

        return $retval;
    }
}
