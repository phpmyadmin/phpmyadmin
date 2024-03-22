<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\Status;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Replication\ReplicationInfo;
use PhpMyAdmin\Url;

use function __;
use function mb_strtolower;
use function str_contains;

/**
 * This class provides data about the server status
 *
 * TODO: Use lazy initialisation for some of the properties
 *       since not all of the server_status_*.php pages need
 *       all the data that this class provides.
 */
final class Data
{
    /**
     * variable name => section
     * variable names match when they begin with the given string
     */
    private const ALLOCATIONS = [
        'Com_' => 'com',
        'Innodb_' => 'innodb',
        'Ndb_' => 'ndb',
        'Handler_' => 'handler',
        'Qcache_' => 'qcache',
        'Threads_' => 'threads',
        'Slow_launch_threads' => 'threads',

        'Binlog_cache_' => 'binlog_cache',
        'Created_tmp_' => 'created_tmp',
        'Key_' => 'key',

        'Delayed_' => 'delayed',
        'Not_flushed_delayed_rows' => 'delayed',

        'Flush_commands' => 'query',
        'Last_query_cost' => 'query',
        'Slow_queries' => 'query',
        'Queries' => 'query',
        'Prepared_stmt_count' => 'query',

        'Select_' => 'select',
        'Sort_' => 'sort',

        'Open_tables' => 'table',
        'Opened_tables' => 'table',
        'Open_table_definitions' => 'table',
        'Opened_table_definitions' => 'table',
        'Table_locks_' => 'table',

        'Rpl_status' => 'repl',
        'Slave_' => 'repl',

        'Tc_' => 'tc',

        'Ssl_' => 'ssl',

        'Open_files' => 'files',
        'Open_streams' => 'files',
        'Opened_files' => 'files',
    ];

    /**
     * @var mixed[]
     * @readonly
     * */
    public array $status;

    /** @var array<string, string> */
    public readonly array $sections;

    /** @var mixed[] */
    public readonly array $variables;

    /**
     * @var mixed[]
     * @readonly
     */
    public array $usedQueries;

    /** @var string[] */
    public readonly array $allocationMap;

    /** @var mixed[] */
    public readonly array $links;

    public readonly bool $dbIsLocal;

    /** @var true[] */
    public readonly array $sectionUsed;

    /** @readonly */
    public bool $dataLoaded;

    private readonly ReplicationInfo $replicationInfo;

    public function getReplicationInfo(): ReplicationInfo
    {
        return $this->replicationInfo;
    }

    /**
     * Returns the map of section => section name (description)
     *
     * @return array<string, string>
     */
    private function getSections(): array
    {
        return [
            'com' => 'Com',
            'query' => __('SQL query'),
            'innodb' => 'InnoDB',
            'ndb' => 'NDB',
            'handler' => __('Handler'),
            'qcache' => __('Query cache'),
            'threads' => __('Threads'),
            'binlog_cache' => __('Binary log'),
            'created_tmp' => __('Temporary data'),
            'delayed' => __('Delayed inserts'),
            'key' => __('Key cache'),
            'select' => __('Joins'),
            'repl' => __('Replication'),
            'sort' => __('Sorting'),
            'table' => __('Tables'),
            'tc' => __('Transaction coordinator'),
            'files' => __('Files'),
            'ssl' => 'SSL',
            'other' => __('Other'),
        ];
    }

    /**
     * Gets the links for constructor
     *
     * @return mixed[]
     */
    private function getLinks(): array
    {
        $primaryInfo = $this->replicationInfo->getPrimaryInfo();
        $replicaInfo = $this->replicationInfo->getReplicaInfo();

        $selfUrl = $this->config->getRootPath();

        $links = [];
        // variable or section name => (name => url)

        $links['table'][__('Flush (close) all tables')] = [
            'url' => $selfUrl,
            'params' => Url::getCommon(['flush' => 'TABLES'], ''),
        ];
        $links['table'][__('Show open tables')] = [
            'url' => Url::getFromRoute('/sql'),
            'params' => Url::getCommon(['sql_query' => 'SHOW OPEN TABLES', 'goto' => $selfUrl], ''),
        ];

        if ($primaryInfo['status']) {
            $links['repl'][__('Show replica hosts')] = [
                'url' => Url::getFromRoute('/sql'),
                'params' => Url::getCommon(['sql_query' => 'SHOW SLAVE HOSTS', 'goto' => $selfUrl], ''),
            ];
            $links['repl'][__('Show primary status')] = ['url' => '#replication_primary', 'params' => ''];
        }

        if ($replicaInfo['status']) {
            $links['repl'][__('Show replica status')] = ['url' => '#replication_replica', 'params' => ''];
        }

        $links['repl']['doc'] = 'replication';

        $links['qcache'][__('Flush query cache')] = [
            'url' => $selfUrl,
            'params' => Url::getCommon(['flush' => 'QUERY CACHE'], ''),
        ];
        $links['qcache']['doc'] = 'query_cache';

        $links['threads']['doc'] = 'mysql_threads';

        $links['key']['doc'] = 'myisam_key_cache';

        $links['binlog_cache']['doc'] = 'binary_log';

        $links['Slow_queries']['doc'] = 'slow_query_log';

        $links['innodb'][__('Variables')] = ['url' => Url::getFromRoute('/server/engines/InnoDB'), 'params' => ''];
        $links['innodb'][__('InnoDB Status')] = [
            'url' => Url::getFromRoute('/server/engines/InnoDB/Status'),
            'params' => '',
        ];
        $links['innodb']['doc'] = 'innodb';

        return $links;
    }

    /**
     * Calculate some values
     *
     * @param mixed[] $serverStatus    contains results of SHOW GLOBAL STATUS
     * @param mixed[] $serverVariables contains results of SHOW GLOBAL VARIABLES
     *
     * @return mixed[]
     */
    private function calculateValues(array $serverStatus, array $serverVariables): array
    {
        // Key_buffer_fraction
        if (
            isset(
                $serverStatus['Key_blocks_unused'],
                $serverVariables['key_cache_block_size'],
                $serverVariables['key_buffer_size'],
            ) && $serverVariables['key_buffer_size'] != 0
        ) {
            $serverStatus['Key_buffer_fraction_%'] = 100
                - $serverStatus['Key_blocks_unused']
                * $serverVariables['key_cache_block_size']
                / $serverVariables['key_buffer_size']
                * 100;
        } elseif (
            isset($serverStatus['Key_blocks_used'], $serverVariables['key_buffer_size'])
            && $serverVariables['key_buffer_size'] != 0
        ) {
            $serverStatus['Key_buffer_fraction_%'] = $serverStatus['Key_blocks_used']
                * 1024
                / $serverVariables['key_buffer_size'];
        }

        // Ratio for key read/write
        if (
            isset($serverStatus['Key_writes'], $serverStatus['Key_write_requests'])
            && $serverStatus['Key_write_requests'] > 0
        ) {
            $keyWrites = $serverStatus['Key_writes'];
            $keyWriteRequests = $serverStatus['Key_write_requests'];
            $serverStatus['Key_write_ratio_%'] = 100 * $keyWrites / $keyWriteRequests;
        }

        if (
            isset($serverStatus['Key_reads'], $serverStatus['Key_read_requests'])
            && $serverStatus['Key_read_requests'] > 0
        ) {
            $keyReads = $serverStatus['Key_reads'];
            $keyReadRequests = $serverStatus['Key_read_requests'];
            $serverStatus['Key_read_ratio_%'] = 100 * $keyReads / $keyReadRequests;
        }

        // Threads_cache_hitrate
        if (
            isset($serverStatus['Threads_created'], $serverStatus['Connections'])
            && $serverStatus['Connections'] > 0
        ) {
            $serverStatus['Threads_cache_hitrate_%'] = 100 - $serverStatus['Threads_created']
                / $serverStatus['Connections'] * 100;
        }

        return $serverStatus;
    }

    /**
     * Sort variables into arrays
     *
     * @param mixed[] $serverStatus contains results of SHOW GLOBAL STATUS
     *
     * @return array{string[], true[], mixed[]}
     */
    private function sortVariables(array $serverStatus): array
    {
        // Variable to contain all com_ variables (query statistics)
        $usedQueries = [];

        // Variable to map variable names to their respective section name
        // (used for js category filtering)
        $allocationMap = [];

        $sectionUsed = [];

        foreach ($serverStatus as $name => $value) {
            foreach (self::ALLOCATIONS as $filter => $section) {
                if (! str_contains($name, $filter)) {
                    continue;
                }

                $allocationMap[$name] = $section;
                $sectionUsed[$section] = true;
                if ($section === 'com' && $value > 0) {
                    $usedQueries[$name] = $value;
                }

                continue 2;
            }

            $allocationMap[$name] = 'other';
            $sectionUsed['other'] = true;
        }

        return [$allocationMap, $sectionUsed, $usedQueries];
    }

    public function __construct(private DatabaseInterface $dbi, private Config $config)
    {
        $this->replicationInfo = new ReplicationInfo($this->dbi);
        $this->replicationInfo->load($_POST['primary_connection'] ?? null);

        $serverStatusResult = $this->dbi->tryQuery('SHOW GLOBAL STATUS');
        if ($serverStatusResult === false) {
            $serverStatus = [];
            $this->dataLoaded = false;
        } else {
            $this->dataLoaded = true;
            $serverStatus = $serverStatusResult->fetchAllKeyPair();
            unset($serverStatusResult);
        }

        // for some calculations we require also some server settings
        $serverVariables = $this->dbi->fetchResult('SHOW GLOBAL VARIABLES', 0, 1);

        $serverStatus = self::cleanDeprecated($serverStatus);

        $serverStatus = $this->calculateValues($serverStatus, $serverVariables);

        $links = $this->getLinks();

        [
            $allocationMap,
            $sectionUsed,
            $usedQueries,
        ] = $this->sortVariables($serverStatus);

        // admin commands are not queries (e.g. they include COM_PING,
        // which is excluded from $server_status['Questions'])
        unset($usedQueries['Com_admin_commands']);

        $serverHostToLower = mb_strtolower($config->selectedServer['host']);
        $this->dbIsLocal = $serverHostToLower === 'localhost'
            || $config->selectedServer['host'] === '127.0.0.1'
            || $config->selectedServer['host'] === '::1';

        $this->status = $serverStatus;
        $this->sections = $this->getSections();
        $this->variables = $serverVariables;
        $this->usedQueries = $usedQueries;
        $this->allocationMap = $allocationMap;
        $this->links = $links;
        $this->sectionUsed = $sectionUsed;
    }

    /**
     * cleanup of some deprecated values
     *
     * @param (string|null)[] $serverStatus status array to process
     *
     * @return (string|null)[]
     */
    public static function cleanDeprecated(array $serverStatus): array
    {
        $deprecated = [
            'Com_prepare_sql' => 'Com_stmt_prepare',
            'Com_execute_sql' => 'Com_stmt_execute',
            'Com_dealloc_sql' => 'Com_stmt_close',
        ];
        foreach ($deprecated as $old => $new) {
            if (! isset($serverStatus[$old], $serverStatus[$new])) {
                continue;
            }

            unset($serverStatus[$old]);
        }

        return $serverStatus;
    }
}
