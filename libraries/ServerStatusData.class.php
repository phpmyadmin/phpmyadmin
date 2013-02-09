<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * PMA_ServerStatusData class
 * Used by server_status_*.php pages
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';

/**
 * This class provides data about the server status
 *
 * All properties of the class are read-only
 *
 * TODO: Use lazy initialisation for some of the properties
 *       since not all of the server_status_*.php pages need
 *       all the data that this class provides.
 *
 * @package PhpMyAdmin
 */
class PMA_ServerStatusData
{
    public $status;
    public $sections;
    public $variables;
    public $used_queries;
    public $allocationMap;
    public $links;
    public $db_isLocal;
    public $section;
    public $categoryUsed;
    public $selfUrl;

    /**
     * An empty setter makes the above properties read-only
     *
     * @param string $a key
     * @param mixed  $b value
     *
     * @return void
     */
    public function __set($a, $b)
    {
        // Discard everything
    }

    /**
     * Constructor
     *
     * @return object
     */
    public function __construct()
    {
        $this->selfUrl = basename($GLOBALS['PMA_PHP_SELF']);
        /**
         * get status from server
         */
        $server_status = PMA_DBI_fetch_result('SHOW GLOBAL STATUS', 0, 1);
        if (PMA_DRIZZLE) {
            // Drizzle doesn't put query statistics into variables, add it
            $sql = "SELECT concat('Com_', variable_name), variable_value
                FROM data_dictionary.GLOBAL_STATEMENTS";
            $statements = PMA_DBI_fetch_result($sql, 0, 1);
            $server_status = array_merge($server_status, $statements);
        }

        /**
         * for some calculations we require also some server settings
         */
        $server_variables = PMA_DBI_fetch_result('SHOW GLOBAL VARIABLES', 0, 1);

        /**
         * cleanup of some deprecated values
         */
        $server_status = self::cleanDeprecated($server_status);

        /**
         * calculate some values
         */
        // Key_buffer_fraction
        if (isset($server_status['Key_blocks_unused'])
            && isset($server_variables['key_cache_block_size'])
            && isset($server_variables['key_buffer_size'])
        ) {
            $server_status['Key_buffer_fraction_%']
                = 100
                - $server_status['Key_blocks_unused']
                * $server_variables['key_cache_block_size']
                / $server_variables['key_buffer_size']
                * 100;
        } elseif (isset($server_status['Key_blocks_used'])
                && isset($server_variables['key_buffer_size'])) {
            $server_status['Key_buffer_fraction_%']
                = $server_status['Key_blocks_used']
                * 1024
                / $server_variables['key_buffer_size'];
        }

        // Ratio for key read/write
        if (isset($server_status['Key_writes'])
            && isset($server_status['Key_write_requests'])
            && $server_status['Key_write_requests'] > 0
        ) {
            $server_status['Key_write_ratio_%']
                = 100 * $server_status['Key_writes'] / $server_status['Key_write_requests'];
        }

        if (isset($server_status['Key_reads'])
            && isset($server_status['Key_read_requests'])
            && $server_status['Key_read_requests'] > 0
        ) {
            $server_status['Key_read_ratio_%']
                = 100 * $server_status['Key_reads'] / $server_status['Key_read_requests'];
        }

        // Threads_cache_hitrate
        if (isset($server_status['Threads_created'])
            && isset($server_status['Connections'])
            && $server_status['Connections'] > 0
        ) {

            $server_status['Threads_cache_hitrate_%']
                = 100 - $server_status['Threads_created']
                / $server_status['Connections'] * 100;
        }

        /**
         * split variables in sections
         */
        $allocations = array(
            // variable name => section
            // variable names match when they begin with the given string

            'Com_'              => 'com',
            'Innodb_'           => 'innodb',
            'Ndb_'              => 'ndb',
            'Handler_'          => 'handler',
            'Qcache_'           => 'qcache',
            'Threads_'          => 'threads',
            'Slow_launch_threads' => 'threads',

            'Binlog_cache_'     => 'binlog_cache',
            'Created_tmp_'      => 'created_tmp',
            'Key_'              => 'key',

            'Delayed_'          => 'delayed',
            'Not_flushed_delayed_rows' => 'delayed',

            'Flush_commands'    => 'query',
            'Last_query_cost'   => 'query',
            'Slow_queries'      => 'query',
            'Queries'           => 'query',
            'Prepared_stmt_count' => 'query',

            'Select_'           => 'select',
            'Sort_'             => 'sort',

            'Open_tables'       => 'table',
            'Opened_tables'     => 'table',
            'Open_table_definitions' => 'table',
            'Opened_table_definitions' => 'table',
            'Table_locks_'      => 'table',

            'Rpl_status'        => 'repl',
            'Slave_'            => 'repl',

            'Tc_'               => 'tc',

            'Ssl_'              => 'ssl',

            'Open_files'        => 'files',
            'Open_streams'      => 'files',
            'Opened_files'      => 'files',
        );

        $sections = array(
            // section => section name (description)
            'com'           => 'Com',
            'query'         => __('SQL query'),
            'innodb'        => 'InnoDB',
            'ndb'           => 'NDB',
            'handler'       => __('Handler'),
            'qcache'        => __('Query cache'),
            'threads'       => __('Threads'),
            'binlog_cache'  => __('Binary log'),
            'created_tmp'   => __('Temporary data'),
            'delayed'       => __('Delayed inserts'),
            'key'           => __('Key cache'),
            'select'        => __('Joins'),
            'repl'          => __('Replication'),
            'sort'          => __('Sorting'),
            'table'         => __('Tables'),
            'tc'            => __('Transaction coordinator'),
            'files'         => __('Files'),
            'ssl'           => 'SSL',
            'other'         => __('Other')
        );

        /**
         * define some needfull links/commands
         */
        // variable or section name => (name => url)
        $links = array();

        $links['table'][__('Flush (close) all tables')]
            = $this->selfUrl . '?flush=TABLES&amp;' . PMA_generate_common_url();
        $links['table'][__('Show open tables')]
            = 'sql.php?sql_query=' . urlencode('SHOW OPEN TABLES') .
                '&amp;goto=' . $this->selfUrl . '&amp;' . PMA_generate_common_url();

        if ($GLOBALS['server_master_status']) {
            $links['repl'][__('Show slave hosts')]
                = 'sql.php?sql_query=' . urlencode('SHOW SLAVE HOSTS')
                    . '&amp;goto=' . $this->selfUrl . '&amp;'
                    . PMA_generate_common_url();
            $links['repl'][__('Show master status')] = '#replication_master';
        }
        if ($GLOBALS['server_slave_status']) {
            $links['repl'][__('Show slave status')] = '#replication_slave';
        }

        $links['repl']['doc'] = 'replication';

        $links['qcache'][__('Flush query cache')]
            = $this->selfUrl . '?flush=' . urlencode('QUERY CACHE') . '&amp;' .
                PMA_generate_common_url();
        $links['qcache']['doc'] = 'query_cache';

        $links['threads']['doc'] = 'mysql_threads';

        $links['key']['doc'] = 'myisam_key_cache';

        $links['binlog_cache']['doc'] = 'binary_log';

        $links['Slow_queries']['doc'] = 'slow_query_log';

        $links['innodb'][__('Variables')]
            = 'server_engines.php?engine=InnoDB&amp;' . PMA_generate_common_url();
        $links['innodb'][__('InnoDB Status')]
            = 'server_engines.php?engine=InnoDB&amp;page=Status&amp;' .
                PMA_generate_common_url();
        $links['innodb']['doc'] = 'innodb';


        // Variable to contain all com_ variables (query statistics)
        $used_queries = array();

        // Variable to map variable names to their respective section name
        // (used for js category filtering)
        $allocationMap = array();

        // Variable to mark used sections
        $categoryUsed = array();

        // sort vars into arrays
        foreach ($server_status as $name => $value) {
            $section_found = false;
            foreach ($allocations as $filter => $section) {
                if (strpos($name, $filter) !== false) {
                    $allocationMap[$name] = $section;
                    $categoryUsed[$section] = true;
                    $section_found = true;
                    if ($section == 'com' && $value > 0) {
                        $used_queries[$name] = $value;
                    }
                    break; // Only exits inner loop
                }
            }
            if (!$section_found) {
                $allocationMap[$name] = 'other';
                $categoryUsed['other'] = true;
            }
        }

        if (PMA_DRIZZLE) {
            $used_queries = PMA_DBI_fetch_result(
                'SELECT * FROM data_dictionary.global_statements',
                0,
                1
            );
            unset($used_queries['admin_commands']);
        } else {
            // admin commands are not queries (e.g. they include COM_PING,
            // which is excluded from $server_status['Questions'])
            unset($used_queries['Com_admin_commands']);
        }

        // Set all class properties
        $this->db_isLocal = false;
        if (strtolower($GLOBALS['cfg']['Server']['host']) === 'localhost'
            || $GLOBALS['cfg']['Server']['host'] === '127.0.0.1'
            || $GLOBALS['cfg']['Server']['host'] === '::1'
        ) {
            $this->db_isLocal = true;
        }
        $this->status = $server_status;
        $this->sections = $sections;
        $this->variables = $server_variables;
        $this->used_queries = $used_queries;
        $this->allocationMap = $allocationMap;
        $this->links = $links;
        $this->categoryUsed = $categoryUsed;
    }

    /**
     * cleanup of some deprecated values
     *
     * @param array $server_status status array to process
     *
     * @return array
     */
    public static function cleanDeprecated($server_status)
    {
        $deprecated = array(
            'Com_prepare_sql' => 'Com_stmt_prepare',
            'Com_execute_sql' => 'Com_stmt_execute',
            'Com_dealloc_sql' => 'Com_stmt_close',
        );
        foreach ($deprecated as $old => $new) {
            if (isset($server_status[$old]) && isset($server_status[$new])) {
                unset($server_status[$old]);
            }
        }
        return $server_status;
    }

    /**
     * cleanup of some deprecated values
     *
     * @return array
     */
    public function getMenuHtml()
    {
        $url_params = PMA_generate_common_url();
        $items = array(
            array(
                'name' => __('Server'),
                'url' => 'server_status.php'
            ),
            array(
                'name' => __('Query statistics'),
                'url' => 'server_status_queries.php'
            ),
            array(
                'name' => __('All status variables'),
                'url' => 'server_status_variables.php'
            ),
            array(
                'name' => __('Monitor'),
                'url' => 'server_status_monitor.php'
            ),
            array(
                'name' => __('Advisor'),
                'url' => 'server_status_advisor.php'
            )
        );

        $retval  = '<ul id="topmenu2">';
        foreach ($items as $item) {
            $class = '';
            if ($item['url'] === $this->selfUrl) {
                $class = ' class="tabactive"';
            }
            $retval .= '<li>';
            $retval .= '<a' . $class;
            $retval .= ' href="' . $item['url'] . '?' . $url_params . '">';
            $retval .= $item['name'];
            $retval .= '</a>';
            $retval .= '</li>';
        }
        $retval .= '</ul>';
        $retval .= '<div class="clearfloat"></div>';

        return $retval;
    }
}

?>
