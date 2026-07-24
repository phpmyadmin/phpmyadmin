<?php
/**
 * Interface to the PDO extension (pdo_mysql)
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use PDO;
use PDOException;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Util;

use function __;
use function defined;
use function extension_loaded;
use function is_numeric;
use function is_string;
use function phpversion;
use function sprintf;
use function str_contains;
use function strtolower;
use function substr;

/**
 * Interface to the PDO extension (pdo_mysql)
 */
class DbiPdo implements DbiExtension
{
    private int $connectionErrorNumber = 0;

    private string $clientVersion = '';

    public function connect(Server $server): Connection
    {
        if (! extension_loaded('pdo_mysql')) {
            throw new ConnectionException(__('The pdo_mysql extension is missing.'));
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_STRINGIFY_FETCHES => true,
            PDO::MYSQL_ATTR_LOCAL_INFILE => defined('PMA_ENABLE_LDI'),
        ];

        if (Config::getInstance()->settings['PersistentConnections']) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }

        /* Optionally compress connection */
        if ($server->compress) {
            $options[PDO::MYSQL_ATTR_COMPRESS] = true;
        }

        /* Optionally enable SSL */
        if ($server->ssl) {
            if ($server->sslKey !== null && $server->sslKey !== '') {
                $options[PDO::MYSQL_ATTR_SSL_KEY] = $server->sslKey;
            }

            if ($server->sslCert !== null && $server->sslCert !== '') {
                $options[PDO::MYSQL_ATTR_SSL_CERT] = $server->sslCert;
            }

            if ($server->sslCa !== null && $server->sslCa !== '') {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $server->sslCa;
            }

            if ($server->sslCaPath !== null && $server->sslCaPath !== '') {
                $options[PDO::MYSQL_ATTR_SSL_CAPATH] = $server->sslCaPath;
            }

            if ($server->sslCiphers !== null && $server->sslCiphers !== '') {
                $options[PDO::MYSQL_ATTR_SSL_CIPHER] = $server->sslCiphers;
            }

            if (! $server->sslVerify && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        if ($server->socket !== '' && ($server->host === '' || $server->host === 'localhost')) {
            $dsn = 'mysql:unix_socket=' . $server->socket;
        } else {
            $dsn = 'mysql:host=' . $server->host;
            if ((int) $server->port !== 0) {
                $dsn .= ';port=' . (int) $server->port;
            }
        }

        try {
            $pdo = new PDO($dsn, $server->user, $server->password, $options);
        } catch (PDOException $exception) {
            /** @var int|string|null $errorCode */
            $errorCode = $exception->errorInfo[1] ?? $exception->getCode();
            $errorNumber = is_numeric($errorCode) ? (int) $errorCode : 0;
            $errorMessage = $exception->getMessage();

            if (! $server->ssl && $this->isSslRequiredByServer($errorNumber, $errorMessage)) {
                return $this->connect($server->withSSL(true));
            }

            $this->connectionErrorNumber = $errorNumber;

            if ($errorNumber === 1045 && $server->hideConnectionErrors) {
                throw new ConnectionException(
                    sprintf(
                        __(
                            'Error 1045: Access denied for user. Additional error information'
                            . ' may be available, but is being hidden by the %s configuration directive.',
                        ),
                        '[code][doc@cfg_Servers_hide_connection_errors]'
                        . '$cfg[\'Servers\'][$i][\'hide_connection_errors\'][/doc][/code]',
                    ),
                    $errorNumber,
                    $exception,
                );
            }

            throw new ConnectionException($errorNumber . ': ' . $errorMessage, $errorNumber, $exception);
        }

        /** @var string|int|null $clientVersion */
        $clientVersion = $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION);
        $this->clientVersion = is_string($clientVersion) ? $clientVersion : '';

        return new Connection(new PdoConnection($pdo));
    }

    /**
     * selects given database
     *
     * @param string|DatabaseName $databaseName database name to select
     */
    public function selectDb(string|DatabaseName $databaseName, Connection $connection): bool
    {
        /** @var PdoConnection $pdoConnection */
        $pdoConnection = $connection->connection;

        return $pdoConnection->pdo->exec('USE ' . Util::backquote((string) $databaseName)) !== false;
    }

    /**
     * runs a query and returns the result
     */
    public function realQuery(string $query, Connection $connection, bool $unbuffered = false): PdoResult|false
    {
        /** @var PdoConnection $pdoConnection */
        $pdoConnection = $connection->connection;

        $pdoConnection->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, ! $unbuffered);
        $statement = $pdoConnection->pdo->query($query);
        $pdoConnection->lastStatement = $statement === false ? null : $statement;

        if ($statement === false) {
            return false;
        }

        return new PdoResult($statement, ! $unbuffered);
    }

    /**
     * Run the multi query and output the results
     *
     * @param string $query multi query statement to execute
     */
    public function realMultiQuery(Connection $connection, string $query): bool
    {
        /** @var PdoConnection $pdoConnection */
        $pdoConnection = $connection->connection;

        $pdoConnection->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $statement = $pdoConnection->pdo->query($query);
        $pdoConnection->lastStatement = $statement === false ? null : $statement;

        return $statement !== false;
    }

    /**
     * Prepare next result from multi_query
     */
    public function nextResult(Connection $connection): bool
    {
        /** @var PdoConnection $pdoConnection */
        $pdoConnection = $connection->connection;

        return $pdoConnection->lastStatement !== null && $pdoConnection->lastStatement->nextRowset();
    }

    /**
     * Store the result returned from multi query
     *
     * @return PdoResult|false false when empty results / result set when not empty
     */
    public function storeResult(Connection $connection): PdoResult|false
    {
        /** @var PdoConnection $pdoConnection */
        $pdoConnection = $connection->connection;

        $statement = $pdoConnection->lastStatement;
        if ($statement === null || $statement->columnCount() === 0) {
            return false;
        }

        return new PdoResult($statement);
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @return string type of connection used
     */
    public function getHostInfo(Connection $connection): string
    {
        /** @var PdoConnection $pdoConnection */
        $pdoConnection = $connection->connection;

        /** @var string|null $hostInfo */
        $hostInfo = $pdoConnection->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);

        return is_string($hostInfo) ? $hostInfo : '';
    }

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo(): string
    {
        if ($this->clientVersion !== '') {
            return $this->clientVersion;
        }

        $version = phpversion('pdo_mysql');

        return $version === false ? '' : $version;
    }

    /**
     * Returns last error message or an empty string if no errors occurred.
     */
    public function getError(Connection $connection): string
    {
        DatabaseInterface::$errorNumber = 0;

        /** @var PdoConnection $pdoConnection */
        $pdoConnection = $connection->connection;

        /** @var array{0: string, 1: int|null, 2: string|null} $errorInfo */
        $errorInfo = $pdoConnection->lastStatement !== null
            && $pdoConnection->lastStatement->errorCode() !== '00000'
            ? $pdoConnection->lastStatement->errorInfo()
            : $pdoConnection->pdo->errorInfo();

        $errorNumber = $errorInfo[1] ?? 0;
        $errorMessage = $errorInfo[2] ?? '';

        if ($errorNumber === 0 || $errorMessage === '') {
            return '';
        }

        // keep the error number for further check after
        // the call to getError()
        DatabaseInterface::$errorNumber = $errorNumber;

        return Utilities::formatError($errorNumber, $errorMessage);
    }

    /**
     * Returns the error code for the most recent connection attempt.
     */
    public function getConnectionErrorNumber(): int
    {
        return $this->connectionErrorNumber;
    }

    /**
     * returns the number of rows affected by last query
     */
    public function affectedRows(Connection $connection): int
    {
        /** @var PdoConnection $pdoConnection */
        $pdoConnection = $connection->connection;

        if ($pdoConnection->lastStatement === null) {
            return -1;
        }

        return $pdoConnection->lastStatement->rowCount();
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param string $string string to be escaped
     *
     * @return string a MySQL escaped string
     */
    public function escapeString(Connection $connection, string $string): string
    {
        /** @var PdoConnection $pdoConnection */
        $pdoConnection = $connection->connection;

        $quoted = $pdoConnection->pdo->quote($string);

        // PDO::quote() wraps the escaped string in single quotes
        return $quoted === false ? '' : substr($quoted, 1, -1);
    }

    /**
     * Execute a prepared statement and return the result.
     *
     * @param list<string> $params
     */
    public function executeQuery(Connection $connection, string $query, array $params): PdoResult|null
    {
        /** @var PdoConnection $pdoConnection */
        $pdoConnection = $connection->connection;

        $pdoConnection->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $statement = $pdoConnection->pdo->prepare($query);
        if ($statement === false) {
            $pdoConnection->lastStatement = null;

            return null;
        }

        $pdoConnection->lastStatement = $statement;

        if (! $statement->execute($params)) {
            return null;
        }

        return new PdoResult($statement);
    }

    /**
     * Returns the number of warnings from the last query.
     */
    public function getWarningCount(Connection $connection): int
    {
        /** @var PdoConnection $pdoConnection */
        $pdoConnection = $connection->connection;

        // Do not go through realQuery() to keep the last statement untouched.
        $statement = $pdoConnection->pdo->query('SHOW COUNT(*) WARNINGS');
        if ($statement === false) {
            return 0;
        }

        return (int) $statement->fetchColumn();
    }

    /**
     * Switch to SSL if server asked us to do so, unfortunately
     * there are more ways MySQL server can tell this:
     *
     * - MySQL 8.0 and newer should return error 3159
     * - #2001 - SSL Connection is required. Please specify SSL options and retry.
     * - #9002 - SSL connection is required. Please specify SSL options and retry.
     */
    private function isSslRequiredByServer(int $errorNumber, string $errorMessage): bool
    {
        return $errorNumber === 3159
            || ($errorNumber === 2001 || $errorNumber === 9002)
            && str_contains(strtolower($errorMessage), 'ssl connection is required');
    }
}
