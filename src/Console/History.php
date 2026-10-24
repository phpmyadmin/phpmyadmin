<?php

declare(strict_types=1);

namespace PhpMyAdmin\Console;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Util;

use function array_reverse;
use function array_shift;
use function count;
use function mb_strlen;

readonly class History
{
    public function __construct(private DatabaseInterface $dbi, private Relation $relation, private Config $config)
    {
    }

    public function setHistory(string $db, string $table, string $username, string $sqlquery): void
    {
        $maxCharactersInDisplayedSQL = $this->config->config->MaxCharactersInDisplayedSQL;
        if (mb_strlen($sqlquery) > $maxCharactersInDisplayedSQL) {
            return;
        }

        if (! isset($_SESSION['sql_history'])) {
            $_SESSION['sql_history'] = [];
        }

        $_SESSION['sql_history'][] = ['db' => $db, 'table' => $table, 'sqlquery' => $sqlquery];

        if (count($_SESSION['sql_history']) > $this->config->config->QueryHistoryMax) {
            array_shift($_SESSION['sql_history']);
        }

        $sqlHistoryFeature = $this->relation->getRelationParameters()->sqlHistoryFeature;
        if ($sqlHistoryFeature === null || ! $this->config->config->QueryHistoryDB) {
            return;
        }

        $this->dbi->queryAsControlUser(
            'INSERT INTO '
            . Util::backquote($sqlHistoryFeature->database) . '.'
            . Util::backquote($sqlHistoryFeature->history) . '
                  (`username`,
                    `db`,
                    `table`,
                    `timevalue`,
                    `sqlquery`)
            VALUES
                  (' . $this->dbi->quoteString($username, ConnectionType::ControlUser) . ',
                   ' . $this->dbi->quoteString($db, ConnectionType::ControlUser) . ',
                   ' . $this->dbi->quoteString($table, ConnectionType::ControlUser) . ',
                   NOW(),
                   ' . $this->dbi->quoteString($sqlquery, ConnectionType::ControlUser) . ')',
        );

        $this->purgeHistory($username);
    }

    /**
     * Gets a SQL history entry
     *
     * @return mixed[]|false list of history items
     */
    public function getHistory(string $username): array|false
    {
        $sqlHistoryFeature = $this->relation->getRelationParameters()->sqlHistoryFeature;
        if ($sqlHistoryFeature === null) {
            return false;
        }

        if (! $this->config->config->QueryHistoryDB) {
            if (isset($_SESSION['sql_history'])) {
                return array_reverse($_SESSION['sql_history']);
            }

            return false;
        }

        $histQuery = '
             SELECT `db`,
                    `table`,
                    `sqlquery`,
                    `timevalue`
               FROM ' . Util::backquote($sqlHistoryFeature->database)
                . '.' . Util::backquote($sqlHistoryFeature->history) . '
              WHERE `username` = ' . $this->dbi->quoteString($username) . '
           ORDER BY `id` DESC';

        return $this->dbi->fetchResultSimple($histQuery, ConnectionType::ControlUser);
    }

    private function purgeHistory(string $username): void
    {
        $sqlHistoryFeature = $this->relation->getRelationParameters()->sqlHistoryFeature;
        if (! $this->config->config->QueryHistoryDB || $sqlHistoryFeature === null) {
            return;
        }

        $searchQuery = '
            SELECT `timevalue`
            FROM ' . Util::backquote($sqlHistoryFeature->database)
                . '.' . Util::backquote($sqlHistoryFeature->history) . '
            WHERE `username` = ' . $this->dbi->quoteString($username) . '
            ORDER BY `timevalue` DESC
            LIMIT ' . $this->config->config->QueryHistoryMax . ', 1';

        $maxTime = $this->dbi->fetchValue($searchQuery, 0, ConnectionType::ControlUser);

        if (! $maxTime) {
            return;
        }

        $this->dbi->queryAsControlUser(
            'DELETE FROM '
            . Util::backquote($sqlHistoryFeature->database) . '.'
            . Util::backquote($sqlHistoryFeature->history) . '
              WHERE `username` = ' . $this->dbi->quoteString($username, ConnectionType::ControlUser)
            . '
                AND `timevalue` <= ' . $this->dbi->quoteString($maxTime, ConnectionType::ControlUser),
        );
    }
}
