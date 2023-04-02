<?php
/**
 * PhpMyAdmin\Server\Status\Data class
 * Used by server_status_*.php pages
 */

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
 * All properties of the class are read-only
 *
 * TODO: Use lazy initialisation for some of the properties
 *       since not all of the server_status_*.php pages need
 *       all the data that this class provides.
 */
class Data
{
    /** @var mixed[] */
    public array $status;

    /** @var mixed[] */
    public array $sections;

    /** @var mixed[] */
    public array $variables;

    /** @var mixed[] */
    public array $usedQueries;

    /** @var mixed[] */
    public array $allocationMap;

    /** @var mixed[] */
    public array $links;

    public bool $dbIsLocal;

    /** @var mixed[] */
    public array $sectionUsed;

    public bool $dataLoaded;

    private ReplicationInfo $replicationInfo;

    public function getReplicationInfo(): ReplicationInfo
    {
        return $this->replicationInfo;
    }

    /**
     * An empty setter makes the above properties read-only
     *
     * @param string $a key
     * @param mixed  $b value
     */
    public function __set(string $a, mixed $b): void
    {
        // Discard everything
    }

    /**
     * Gets the allocations for constructor
     *
     * @return mixed[]
     */
    private function getAllocations(): array
    {
        return [
            // variable name => section
            // variable names match when they begin with the given string

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
    }

    /**
     * Gets the sections for constructor
     *
     * @return mixed[]
     */
    private function getSections(): array
    {
        return [
            // section => section name (description)
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
            isset($serverStatus['Key_blocks_unused'], $serverVariables['key_cache_block_size'])
            && isset($serverVariables['key_buffer_size'])
            && $serverVariables['key_buffer_size'] != 0
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
     * @param mixed[] $serverStatus  contains results of SHOW GLOBAL STATUS
     * @param mixed[] $allocations   allocations for sections
     * @param mixed[] $allocationMap map variables to their section
     * @param mixed[] $sectionUsed   is a section used?
     * @param mixed[] $usedQueries   used queries
     *
     * @return mixed[] ($allocationMap, $sectionUsed, $used_queries)
     */
    private function sortVariables(
        array $serverStatus,
        array $allocations,
        array $allocationMap,
        array $sectionUsed,
        array $usedQueries,
    ): array {
        foreach ($serverStatus as $name => $value) {
            $sectionFound = false;
            foreach ($allocations as $filter => $section) {
                if (! str_contains($name, $filter)) {
                    continue;
                }

                $allocationMap[$name] = $section;
                $sectionUsed[$section] = true;
                $sectionFound = true;
                if ($section === 'com' && $value > 0) {
                    $usedQueries[$name] = $value;
                }

                break; // Only exits inner loop
            }

            if ($sectionFound) {
                continue;
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

        // get status from server
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

        // cleanup of some deprecated values
        $serverStatus = self::cleanDeprecated($serverStatus);

        // calculate some values
        $serverStatus = $this->calculateValues($serverStatus, $serverVariables);

        // split variables in sections
        $allocations = $this->getAllocations();

        $sections = $this->getSections();

        // define some needful links/commands
        $links = $this->getLinks();

        // Variable to contain all com_ variables (query statistics)
        $usedQueries = [];

        // Variable to map variable names to their respective section name
        // (used for js category filtering)
        $allocationMap = [];

        // Variable to mark used sections
        $sectionUsed = [];

        // sort vars into arrays
        [
            $allocationMap,
            $sectionUsed,
            $usedQueries,
        ] = $this->sortVariables($serverStatus, $allocations, $allocationMap, $sectionUsed, $usedQueries);

        // admin commands are not queries (e.g. they include COM_PING,
        // which is excluded from $server_status['Questions'])
        unset($usedQueries['Com_admin_commands']);

        // Set all class properties
        $this->dbIsLocal = false;
        // can be null if $cfg['ServerDefault'] = 0;
        $serverHostToLower = mb_strtolower((string) $GLOBALS['cfg']['Server']['host']);
        if (
            $serverHostToLower === 'localhost'
            || $GLOBALS['cfg']['Server']['host'] === '127.0.0.1'
            || $GLOBALS['cfg']['Server']['host'] === '::1'
        ) {
            $this->dbIsLocal = true;
        }

        $this->status = $serverStatus;
        $this->sections = $sections;
        $this->variables = $serverVariables;
        $this->usedQueries = $usedQueries;
        $this->allocationMap = $allocationMap;
        $this->links = $links;
        $this->sectionUsed = $sectionUsed;
    }

    /**
     * cleanup of some deprecated values
     *
     * @param mixed[] $serverStatus status array to process
     *
     * @return mixed[]
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
