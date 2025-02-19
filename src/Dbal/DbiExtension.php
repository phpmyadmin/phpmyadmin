<?php
/**
 * Contract for every database extension supported by phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Identifiers\DatabaseName;

/**
 * Contract for every database extension supported by phpMyAdmin
 */
interface DbiExtension
{
    /**
     * Connects to the database server.
     *
     * @throws ConnectionException
     */
    public function connect(Server $server): Connection;

    /**
     * selects given database
     *
     * @param string|DatabaseName $databaseName database name to select
     */
    public function selectDb(string|DatabaseName $databaseName, Connection $connection): bool;

    /**
     * runs a query and returns the result
     *
     * @return ResultInterface|false result
     */
    public function realQuery(string $query, Connection $connection, bool $unbuffered = false): ResultInterface|false;

    /**
     * Run the multi query and output the results
     *
     * @param string $query multi query statement to execute
     */
    public function realMultiQuery(Connection $connection, string $query): bool;

    /**
     * Prepare next result from multi_query
     */
    public function nextResult(Connection $connection): bool;

    /**
     * Store the result returned from multi query
     *
     * @return ResultInterface|false false when empty results / result set when not empty
     */
    public function storeResult(Connection $connection): ResultInterface|false;

    /**
     * Returns a string representing the type of connection used
     *
     * @return string type of connection used
     */
    public function getHostInfo(Connection $connection): string;

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo(): string;

    /**
     * Returns last error message or an empty string if no errors occurred.
     */
    public function getError(Connection $connection): string;

    /**
     * Returns the error code for the most recent connection attempt.
     */
    public function getConnectionErrorNumber(): int;

    /**
     * returns the number of rows affected by last query
     *
     * @psalm-return int|numeric-string
     */
    public function affectedRows(Connection $connection): int|string;

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param string $string string to be escaped
     *
     * @return string a MySQL escaped string
     */
    public function escapeString(Connection $connection, string $string): string;

    /**
     * Execute a prepared statement and return the result.
     *
     * @param list<string> $params
     */
    public function executeQuery(Connection $connection, string $query, array $params): ResultInterface|null;

    /**
     * Returns the number of warnings from the last query.
     */
    public function getWarningCount(Connection $connection): int;
}
