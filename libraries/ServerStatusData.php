<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * ServerStatusData class
 * Used by server_status_*.php pages
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

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
class ServerStatusData
{
    public $status;
    public $sections;
    public $variables;
    public $used_queries;
    public $allocationMap;
    public $links;
    public $db_isLocal;
    public $section;
    public $sectionUsed;
    public $selfUrl;
    public $dataLoaded;

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
     * Gets the allocations for constructor
     *
     * @return array
     */
    private function _getAllocations()
    {
        return array(
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
    }

    /**
     * Gets the sections for constructor
     *
     * @return array
     */
    private function _getSections()
    {
        return array(
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
    }

    /**
     * Gets the links for constructor
     *
     * @return array
     */
    private function _getLinks()
    {
        $links = array();
        // variable or section name => (name => url)

        $links['table'][__('Flush (close) all tables')] = $this->selfUrl
            . PMA_URL_getCommon(
                array(
                    'flush' => 'TABLES'
                )
            );
        $links['table'][__('Show open tables')]
            = 'sql.php' . PMA_URL_getCommon(
                array(
                    'sql_query' => 'SHOW OPEN TABLES',
                    'goto' => $this->selfUrl,
                )
            );

        if ($GLOBALS['replication_info']['master']['status']) {
            $links['repl'][__('Show slave hosts')]
                = 'sql.php' . PMA_URL_getCommon(
                    array(
                        'sql_query' => 'SHOW SLAVE HOSTS',
                        'goto' => $this->selfUrl,
                    )
                );
            $links['repl'][__('Show master status')] = '#replication_master';
        }
        if ($GLOBALS['replication_info']['slave']['status']) {
            $links['repl'][__('Show slave status')] = '#replication_slave';
        }

        $links['repl']['doc'] = 'replication';

        $links['qcache'][__('Flush query cache')]
            = $this->selfUrl
            . PMA_URL_getCommon(
                array(
                    'flush' => 'QUERY CACHE'
                )
            );
        $links['qcache']['doc'] = 'query_cache';

        $links['threads']['doc'] = 'mysql_threads';

        $links['key']['doc'] = 'myisam_key_cache';

        $links['binlog_cache']['doc'] = 'binary_log';

        $links['Slow_queries']['doc'] = 'slow_query_log';

        $links['innodb'][__('Variables')]
            = 'server_engines.php?engine=InnoDB&amp;'
            . PMA_URL_getCommon(array(), 'html', '');
        $links['innodb'][__('InnoDB Status')]
            = 'server_engines.php'
            . PMA_URL_getCommon(
                array(
                    'engine' => 'InnoDB',
                    'page' => 'Status'
                )
            );
        $links['innodb']['doc'] = 'innodb';

        return($links);
    }

    /**
     * Calculate some values
     *
     * @param array $server_status    contains results of SHOW GLOBAL STATUS
     * @param array $server_variables contains results of SHOW GLOBAL VARIABLES
     *
     * @return array $server_status
     */
    private function _calculateValues($server_status, $server_variables)
    {
        // Key_buffer_fraction
        if (isset($server_status['Key_blocks_unused'])
            && isset($server_variables['key_cache_block_size'])
            && isset($server_variables['key_buffer_size'])
            && $server_variables['key_buffer_size'] != 0
        ) {
            $server_status['Key_buffer_fraction_%']
                = 100
                - $server_status['Key_blocks_unused']
                * $server_variables['key_cache_block_size']
                / $server_variables['key_buffer_size']
                * 100;
        } elseif (isset($server_status['Key_blocks_used'])
            && isset($server_variables['key_buffer_size'])
            && $server_variables['key_buffer_size'] != 0
        ) {
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
            $key_writes = $server_status['Key_writes'];
            $key_write_requests = $server_status['Key_write_requests'];
            $server_status['Key_write_ratio_%']
                = 100 * $key_writes / $key_write_requests;
        }

        if (isset($server_status['Key_reads'])
            && isset($server_status['Key_read_requests'])
            && $server_status['Key_read_requests'] > 0
        ) {
            $key_reads = $server_status['Key_reads'];
            $key_read_requests = $server_status['Key_read_requests'];
            $server_status['Key_read_ratio_%']
                = 100 * $key_reads / $key_read_requests;
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
        return $server_status;
    }

    /**
     * Sort variables into arrays
     *
     * @param array $server_status contains results of SHOW GLOBAL STATUS
     * @param array $allocations   allocations for sections
     * @param array $allocationMap map variables to their section
     * @param array $sectionUsed   is a section used?
     * @param array $used_queries  used queries
     *
     * @return array ($allocationMap, $sectionUsed, $used_queries)
     */
    private function _sortVariables(
        $server_status, $allocations, $allocationMap, $sectionUsed,
        $used_queries
    ) {
        foreach ($server_status as $name => $value) {
            $section_found = false;
            foreach ($allocations as $filter => $section) {
                if (mb_strpos($name, $filter) !== false) {
                    $allocationMap[$name] = $section;
                    $sectionUsed[$section] = true;
                    $section_found = true;
                    if ($section == 'com' && $value > 0) {
                        $used_queries[$name] = $value;
                    }
                    break; // Only exits inner loop
                }
            }
            if (! $section_found) {
                $allocationMap[$name] = 'other';
                $sectionUsed['other'] = true;
            }
        }
        return array($allocationMap, $sectionUsed, $used_queries);
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->selfUrl = basename($GLOBALS['PMA_PHP_SELF']);

        // get status from server
        $server_status_result = $GLOBALS['dbi']->tryQuery('SHOW GLOBAL STATUS');
        $server_status = array();
        if ($server_status_result === false) {
            $this->dataLoaded = false;
        } else {
            $this->dataLoaded = true;
            while ($arr = $GLOBALS['dbi']->fetchRow($server_status_result)) {
                $server_status[$arr[0]] = $arr[1];
            }
            $GLOBALS['dbi']->freeResult($server_status_result);
        }

        // for some calculations we require also some server settings
        $server_variables = $GLOBALS['dbi']->fetchResult(
            'SHOW GLOBAL VARIABLES', 0, 1
        );

        // cleanup of some deprecated values
        $server_status = self::cleanDeprecated($server_status);

        // calculate some values
        $server_status = $this->_calculateValues(
            $server_status, $server_variables
        );

        // split variables in sections
        $allocations = $this->_getAllocations();

        $sections = $this->_getSections();

        // define some needful links/commands
        $links = $this->_getLinks();

        // Variable to contain all com_ variables (query statistics)
        $used_queries = array();

        // Variable to map variable names to their respective section name
        // (used for js category filtering)
        $allocationMap = array();

        // Variable to mark used sections
        $sectionUsed = array();

        // sort vars into arrays
        list(
            $allocationMap, $sectionUsed, $used_queries
        ) = $this->_sortVariables(
            $server_status, $allocations, $allocationMap, $sectionUsed,
            $used_queries
        );

        // admin commands are not queries (e.g. they include COM_PING,
        // which is excluded from $server_status['Questions'])
        unset($used_queries['Com_admin_commands']);

        // Set all class properties
        $this->db_isLocal = false;
        $serverHostToLower = mb_strtolower(
            $GLOBALS['cfg']['Server']['host']
        );
        if ($serverHostToLower === 'localhost'
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
        $this->sectionUsed = $sectionUsed;
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
     * Generates menu HTML
     *
     * @return string
     */
    public function getMenuHtml()
    {
        $url_params = PMA_URL_getCommon();
        $items = array(
            array(
                'name' => __('Server'),
                'url' => 'server_status.php'
            ),
            array(
                'name' => __('Processes'),
                'url' => 'server_status_processes.php'
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
            $retval .= ' href="' . $item['url'] . $url_params . '">';
            $retval .= $item['name'];
            $retval .= '</a>';
            $retval .= '</li>';
        }
        $retval .= '</ul>';
        $retval .= '<div class="clearfloat"></div>';

        return $retval;
    }

    /**
     * Builds a <select> list for refresh rates
     *
     * @param string $name         Name of select
     * @param int    $defaultRate  Currently chosen rate
     * @param array  $refreshRates List of refresh rates
     *
     * @return string
     */
    public static function getHtmlForRefreshList($name,
        $defaultRate = 5,
        $refreshRates = Array(1, 2, 5, 10, 20, 40, 60, 120, 300, 600)
    ) {
        $return = '<select name="' . $name . '" id="id_' . $name
            . '" class="refreshRate">';
        foreach ($refreshRates as $rate) {
            $selected = ($rate == $defaultRate)?' selected="selected"':'';
            $return .= '<option value="' . $rate . '"' . $selected . '>';
            if ($rate < 60) {
                $return .= sprintf(
                    _ngettext('%d second', '%d seconds', $rate), $rate
                );
            } else {
                $rate = $rate / 60;
                $return .= sprintf(
                    _ngettext('%d minute', '%d minutes', $rate), $rate
                );
            }
            $return .=  '</option>';
        }
        $return .= '</select>';
        return $return;
    }
}

