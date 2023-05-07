<?php

declare(strict_types=1);

namespace PhpMyAdmin\Replication;

use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\ResultInterface;

use function explode;
use function mb_strtoupper;

/**
 * Replication helpers
 *
 * @psalm-import-type ConnectionType from Connection
 */
class Replication
{
    public function __construct(private DatabaseInterface $dbi)
    {
    }

    /**
     * Extracts database or table name from string
     *
     * @param string $string contains "dbname.tablename"
     * @param string $what   what to extract (db|table)
     *
     * @return string the extracted part
     */
    public function extractDbOrTable(string $string, string $what = 'db'): string
    {
        $list = explode('.', $string);
        if ($what === 'db') {
            return $list[0];
        }

        return $list[1];
    }

    /**
     * Configures replication replica
     *
     * @param string      $action  possible values: START or STOP
     * @param string|null $control default: null,
     *                             possible values: SQL_THREAD or IO_THREAD or null.
     *                             If it is set to null, it controls both
     *                             SQL_THREAD and IO_THREAD
     * @psalm-param ConnectionType $connectionType
     *
     * @return ResultInterface|false|int output of DatabaseInterface::tryQuery
     */
    public function replicaControl(string $action, string|null $control, int $connectionType): ResultInterface|false|int
    {
        $action = mb_strtoupper($action);
        $control = $control !== null ? mb_strtoupper($control) : '';

        if ($action !== 'START' && $action !== 'STOP') {
            return -1;
        }

        if ($control !== 'SQL_THREAD' && $control !== 'IO_THREAD' && $control != null) {
            return -1;
        }

        return $this->dbi->tryQuery($action . ' SLAVE ' . $control . ';', $connectionType);
    }

    /**
     * Changes primary for replication replica
     *
     * @param string  $user     replication user on primary
     * @param string  $password password for the user
     * @param string  $host     primary's hostname or IP
     * @param int     $port     port, where mysql is running
     * @param mixed[] $pos      position of mysql replication, array should contain fields File and Position
     * @param bool    $stop     shall we stop replica?
     * @param bool    $start    shall we start replica?
     * @psalm-param ConnectionType $connectionType
     *
     * @return ResultInterface|false output of CHANGE MASTER mysql command
     */
    public function replicaChangePrimary(
        string $user,
        string $password,
        string $host,
        int $port,
        array $pos,
        bool $stop,
        bool $start,
        int $connectionType,
    ): ResultInterface|false {
        if ($stop) {
            $this->replicaControl('STOP', null, $connectionType);
        }

        $out = $this->dbi->tryQuery(
            'CHANGE MASTER TO ' .
            'MASTER_HOST=' . $this->dbi->quoteString($host) . ',' .
            'MASTER_PORT=' . $port . ',' .
            'MASTER_USER=' . $this->dbi->quoteString($user) . ',' .
            'MASTER_PASSWORD=' . $this->dbi->quoteString($password) . ',' .
            'MASTER_LOG_FILE=' . $this->dbi->quoteString($pos['File']) . ',' .
            'MASTER_LOG_POS=' . $pos['Position'] . ';',
            $connectionType,
        );

        if ($start) {
            $this->replicaControl('START', null, $connectionType);
        }

        return $out;
    }

    /**
     * This function provides connection to remote mysql server
     *
     * @param string      $user     mysql username
     * @param string      $password password for the user
     * @param string|null $host     mysql server's hostname or IP
     * @param int|null    $port     mysql remote port
     * @param string|null $socket   path to unix socket
     */
    public function connectToPrimary(
        string $user,
        string $password,
        string|null $host = null,
        int|null $port = null,
        string|null $socket = null,
    ): Connection|null {
        $currentServer = new Server([
            'user' => $user,
            'password' => $password,
            'host' => Core::sanitizeMySQLHost($host ?? ''),
            'port' => $port,
            'socket' => $socket,
        ]);

        // 5th parameter set to true means that it's an auxiliary connection
        // and we must not go back to login page if it fails
        return $this->dbi->connect($currentServer, Connection::TYPE_AUXILIARY);
    }

    /**
     * Fetches position and file of current binary log on primary
     *
     * @psalm-param ConnectionType $connectionType
     *
     * @return mixed[] an array containing File and Position in MySQL replication
     * on primary server, useful for {@see Replication::replicaChangePrimary()}.
     * @phpstan-return array{'File'?: string, 'Position'?: string}
     */
    public function replicaBinLogPrimary(int $connectionType): array
    {
        $data = $this->dbi->fetchResult('SHOW MASTER STATUS', null, null, $connectionType);
        $output = [];

        if ($data !== []) {
            $output['File'] = $data[0]['File'];
            $output['Position'] = $data[0]['Position'];
        }

        return $output;
    }
}
