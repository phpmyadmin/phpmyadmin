<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The InnoDB storage engine
 *
 * @package PhpMyAdmin-Engines
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * The InnoDB storage engine
 *
 * @package PhpMyAdmin-Engines
 */
class PMA_StorageEngine_Innodb extends PMA_StorageEngine
{
    /**
     * Returns array with variable names related to InnoDB storage engine
     *
     * @return array   variable names
     */
    public function getVariables()
    {
        return array(
            'innodb_data_home_dir' => array(
                'title' => __('Data home directory'),
                'desc'  => __(
                    'The common part of the directory path for all InnoDB data '
                    . 'files.'
                ),
            ),
            'innodb_data_file_path' => array(
                'title' => __('Data files'),
            ),
            'innodb_autoextend_increment' => array(
                'title' => __('Autoextend increment'),
                'desc'  => __(
                    'The increment size for extending the size of an autoextending '
                    . 'tablespace when it becomes full.'
                ),
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'innodb_buffer_pool_size' => array(
                'title' => __('Buffer pool size'),
                'desc'  => __(
                    'The size of the memory buffer InnoDB uses to cache data and '
                    . 'indexes of its tables.'
                ),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'innodb_additional_mem_pool_size' => array(
                'title' => 'innodb_additional_mem_pool_size',
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'innodb_buffer_pool_awe_mem_mb' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'innodb_checksums' => array(
            ),
            'innodb_commit_concurrency' => array(
            ),
            'innodb_concurrency_tickets' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'innodb_doublewrite' => array(
            ),
            'innodb_fast_shutdown' => array(
            ),
            'innodb_file_io_threads' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'innodb_file_per_table' => array(
            ),
            'innodb_flush_log_at_trx_commit' => array(
            ),
            'innodb_flush_method' => array(
            ),
            'innodb_force_recovery' => array(
            ),
            'innodb_lock_wait_timeout' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'innodb_locks_unsafe_for_binlog' => array(
            ),
            'innodb_log_arch_dir' => array(
            ),
            'innodb_log_archive' => array(
            ),
            'innodb_log_buffer_size' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'innodb_log_file_size' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'innodb_log_files_in_group' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'innodb_log_group_home_dir' => array(
            ),
            'innodb_max_dirty_pages_pct' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'innodb_max_purge_lag' => array(
            ),
            'innodb_mirrored_log_groups' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'innodb_open_files' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'innodb_support_xa' => array(
            ),
            'innodb_sync_spin_loops' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'innodb_table_locks' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_BOOLEAN,
            ),
            'innodb_thread_concurrency' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'innodb_thread_sleep_delay' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
         );
    }

    /**
     * Returns the pattern to be used in the query for SQL variables
     * related to InnoDb storage engine
     *
     * @return string  SQL query LIKE pattern
     */
    public function getVariablesLikePattern()
    {
        return 'innodb\\_%';
    }

    /**
     * Get information pages
     *
     * @return array detail pages
     */
    public function getInfoPages()
    {
        if ($this->support < PMA_ENGINE_SUPPORT_YES) {
            return array();
        }
        $pages = array();
        $pages['Bufferpool'] = __('Buffer Pool');
        $pages['Status'] = __('InnoDB Status');
        return $pages;
    }

    /**
     * returns html tables with stats over inno db buffer pool
     *
     * @return string  html table with stats
     */
    public function getPageBufferpool()
    {
        // The following query is only possible because we know
        // that we are on MySQL 5 here (checked above)!
        // side note: I love MySQL 5 for this. :-)
        $sql = '
             SHOW STATUS
            WHERE Variable_name LIKE \'Innodb\\_buffer\\_pool\\_%\'
               OR Variable_name = \'Innodb_page_size\';';
        $status = $GLOBALS['dbi']->fetchResult($sql, 0, 1);

        $output = '<table class="data" id="table_innodb_bufferpool_usage">' . "\n"
            . '    <caption class="tblHeaders">' . "\n"
            . '        ' . __('Buffer Pool Usage') . "\n"
            . '    </caption>' . "\n"
            . '    <tfoot>' . "\n"
            . '        <tr>' . "\n"
            . '            <th colspan="2">' . "\n"
            . '                ' . __('Total') . "\n"
            . '                : '
            . PMA_Util::formatNumber(
                $status['Innodb_buffer_pool_pages_total'], 0
            )
            . '&nbsp;' . __('pages')
            . ' / '
            . join(
                '&nbsp;',
                PMA_Util::formatByteDown(
                    $status['Innodb_buffer_pool_pages_total']
                    * $status['Innodb_page_size']
                )
            ) . "\n"
            . '            </th>' . "\n"
            . '        </tr>' . "\n"
            . '    </tfoot>' . "\n"
            . '    <tbody>' . "\n"
            . '        <tr class="odd">' . "\n"
            . '            <th>' . __('Free pages') . '</th>' . "\n"
            . '            <td class="value">'
            . PMA_Util::formatNumber(
                $status['Innodb_buffer_pool_pages_free'], 0
            )
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr class="even">' . "\n"
            . '            <th>' . __('Dirty pages') . '</th>' . "\n"
            . '            <td class="value">'
            . PMA_Util::formatNumber(
                $status['Innodb_buffer_pool_pages_dirty'], 0
            )
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr class="odd">' . "\n"
            . '            <th>' . __('Pages containing data') . '</th>' . "\n"
            . '            <td class="value">'
            . PMA_Util::formatNumber(
                $status['Innodb_buffer_pool_pages_data'], 0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr class="even">' . "\n"
            . '            <th>' . __('Pages to be flushed') . '</th>' . "\n"
            . '            <td class="value">'
            . PMA_Util::formatNumber(
                $status['Innodb_buffer_pool_pages_flushed'], 0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr class="odd">' . "\n"
            . '            <th>' . __('Busy pages') . '</th>' . "\n"
            . '            <td class="value">'
            . PMA_Util::formatNumber(
                $status['Innodb_buffer_pool_pages_misc'], 0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>';

        // not present at least since MySQL 5.1.40
        if (isset($status['Innodb_buffer_pool_pages_latched'])) {
            $output .= '        <tr class="even">'
            . '            <th>' . __('Latched pages') . '</th>'
            . '            <td class="value">'
            . PMA_Util::formatNumber(
                $status['Innodb_buffer_pool_pages_latched'], 0
            )
            . '</td>'
            . '        </tr>';
        }

        $output .= '    </tbody>' . "\n"
            . '</table>' . "\n\n"
            . '<table class="data" id="table_innodb_bufferpool_activity">' . "\n"
            . '    <caption class="tblHeaders">' . "\n"
            . '        ' . __('Buffer Pool Activity') . "\n"
            . '    </caption>' . "\n"
            . '    <tbody>' . "\n"
            . '        <tr class="odd">' . "\n"
            . '            <th>' . __('Read requests') . '</th>' . "\n"
            . '            <td class="value">'
            . PMA_Util::formatNumber(
                $status['Innodb_buffer_pool_read_requests'], 0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr class="even">' . "\n"
            . '            <th>' . __('Write requests') . '</th>' . "\n"
            . '            <td class="value">'
            . PMA_Util::formatNumber(
                $status['Innodb_buffer_pool_write_requests'], 0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr class="odd">' . "\n"
            . '            <th>' . __('Read misses') . '</th>' . "\n"
            . '            <td class="value">'
            . PMA_Util::formatNumber(
                $status['Innodb_buffer_pool_reads'], 0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr class="even">' . "\n"
            . '            <th>' . __('Write waits') . '</th>' . "\n"
            . '            <td class="value">'
            . PMA_Util::formatNumber(
                $status['Innodb_buffer_pool_wait_free'], 0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr class="odd">' . "\n"
            . '            <th>' . __('Read misses in %') . '</th>' . "\n"
            . '            <td class="value">'
            . ($status['Innodb_buffer_pool_read_requests'] == 0
                ? '---'
                : htmlspecialchars(
                    PMA_Util::formatNumber(
                        $status['Innodb_buffer_pool_reads'] * 100
                        / $status['Innodb_buffer_pool_read_requests'],
                        3,
                        2
                    )
                ) . ' %') . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr class="even">' . "\n"
            . '            <th>' . __('Write waits in %') . '</th>' . "\n"
            . '            <td class="value">'
            . ($status['Innodb_buffer_pool_write_requests'] == 0
                ? '---'
                : htmlspecialchars(
                    PMA_Util::formatNumber(
                        $status['Innodb_buffer_pool_wait_free'] * 100
                        / $status['Innodb_buffer_pool_write_requests'],
                        3,
                        2
                    )
                ) . ' %') . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '    </tbody>' . "\n"
            . '</table>' . "\n";
        return $output;
    }

    /**
     * returns InnoDB status
     *
     * @return string  result of SHOW ENGINE INNODB STATUS inside pre tags
     */
    public function getPageStatus()
    {
        return '<pre id="pre_innodb_status">' . "\n"
        . htmlspecialchars(
            $GLOBALS['dbi']->fetchValue('SHOW ENGINE INNODB STATUS;', 0, 'Status')
        ) . "\n"
        . '</pre>' . "\n";
    }

    /**
     * returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    public function getMysqlHelpPage()
    {
        return 'innodb-storage-engine';
    }

    /**
     * Gets the InnoDB plugin version number
     *
     * http://www.innodb.com/products/innodb_plugin
     * (do not confuse this with phpMyAdmin's storage engine plugins!)
     *
     * @return string the version number, or empty if not running as a plugin
     */
    public function getInnodbPluginVersion()
    {
        return $GLOBALS['dbi']->fetchValue('SELECT @@innodb_version;');
    }

    /**
     * Gets the InnoDB file format
     *
     * (works only for the InnoDB plugin)
     * http://www.innodb.com/products/innodb_plugin
     * (do not confuse this with phpMyAdmin's storage engine plugins!)
     *
     * @return string the InnoDB file format
     */
    public function getInnodbFileFormat()
    {
        return $GLOBALS['dbi']->fetchValue(
            "SHOW GLOBAL VARIABLES LIKE 'innodb_file_format';", 0, 1
        );
    }

    /**
     * Verifies if this server supports the innodb_file_per_table feature
     *
     * (works only for the InnoDB plugin)
     * http://www.innodb.com/products/innodb_plugin
     * (do not confuse this with phpMyAdmin's storage engine plugins!)
     *
     * @return boolean whether this feature is supported or not
     */
    public function supportsFilePerTable()
    {
        if ($GLOBALS['dbi']->fetchValue(
            "SHOW GLOBAL VARIABLES LIKE 'innodb_file_per_table';", 0, 1
        ) == 'ON') {
            return true;
        } else {
            return false;
        }

    }
}

