<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id$
 */

/**
 *
 */
class PMA_StorageEngine_innodb extends PMA_StorageEngine
{
    /**
     * @uses    $GLOBALS['strInnoDBDataHomeDir']
     * @uses    $GLOBALS['strInnoDBDataHomeDirDesc']
     * @uses    $GLOBALS['strInnoDBDataFilePath']
     * @uses    $GLOBALS['strInnoDBAutoextendIncrement']
     * @uses    $GLOBALS['strInnoDBAutoextendIncrementDesc']
     * @uses    $GLOBALS['strInnoDBBufferPoolSize']
     * @uses    $GLOBALS['strInnoDBBufferPoolSizeDesc']
     * @uses    PMA_ENGINE_DETAILS_TYPE_NUMERIC
     * @uses    PMA_ENGINE_DETAILS_TYPE_SIZE
     * @return  array
     */
    function getVariables()
    {
        return array(
            'innodb_data_home_dir' => array(
                'title' => $GLOBALS['strInnoDBDataHomeDir'],
                'desc'  => $GLOBALS['strInnoDBDataHomeDirDesc'],
            ),
            'innodb_data_file_path' => array(
                'title' => $GLOBALS['strInnoDBDataFilePath'],
            ),
            'innodb_autoextend_increment' => array(
                'title' => $GLOBALS['strInnoDBAutoextendIncrement'],
                'desc'  => $GLOBALS['strInnoDBAutoextendIncrementDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'innodb_buffer_pool_size' => array(
                'title' => $GLOBALS['strInnoDBBufferPoolSize'],
                'desc'  => $GLOBALS['strInnoDBBufferPoolSizeDesc'],
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
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
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
     * @return  string  SQL query LIKE pattern
     */
    function getVariablesLikePattern()
    {
        return 'innodb\\_%';
    }

    /**
     * @uses    $this->support
     * @uses    PMA_ENGINE_SUPPORT_YES
     * @uses    PMA_MYSQL_INT_VERSION
     * @uses    $GLOBALS['strBufferPool']
     * @uses    $GLOBALS['strInnodbStat']
     * @return  array   detail pages
     */
    function getInfoPages()
    {
        if ($this->support < PMA_ENGINE_SUPPORT_YES) {
            return array();
        }
        $pages = array();
        if (PMA_MYSQL_INT_VERSION >= 50002) {
            $pages['Bufferpool'] = $GLOBALS['strBufferPool'];
        }
        $pages['Status'] = $GLOBALS['strInnodbStat'];
        return $pages;
    }

    /**
     * returns html tables with stats over inno db buffer pool
     *
     * @uses    PMA_MYSQL_INT_VERSION
     * @uses    PMA_DBI_fetch_result()
     * @uses    PMA_formatNumber()
     * @uses    PMA_formatByteDown()
     * @uses    $GLOBALS['strBufferPoolUsage']
     * @uses    $GLOBALS['strTotalUC']
     * @uses    $GLOBALS['strInnoDBPages']
     * @uses    $GLOBALS['strFreePages']
     * @uses    $GLOBALS['strDirtyPages']
     * @uses    $GLOBALS['strDataPages']
     * @uses    $GLOBALS['strPagesToBeFlushed']
     * @uses    $GLOBALS['strBusyPages']
     * @uses    $GLOBALS['strLatchedPages']
     * @uses    $GLOBALS['strBufferPoolActivity']
     * @uses    $GLOBALS['strReadRequests']
     * @uses    $GLOBALS['strWriteRequests']
     * @uses    $GLOBALS['strBufferReadMisses']
     * @uses    $GLOBALS['strBufferWriteWaits']
     * @uses    $GLOBALS['strBufferReadMissesInPercent']
     * @uses    $GLOBALS['strBufferWriteWaitsInPercent']
     * @uses    join()
     * @uses    htmlspecialchars()
     * @uses    PMA_formatNumber()
     * @return  string  html table with stats
     */
    function getPageBufferpool()
    {
        if (PMA_MYSQL_INT_VERSION < 50002) {
            return false;
        }
        // rabus: The following query is only possible because we know
        // that we are on MySQL 5 here (checked above)!
        // side note: I love MySQL 5 for this. :-)
        $sql = '
             SHOW STATUS
            WHERE Variable_name LIKE \'Innodb\\_buffer\\_pool\\_%\'
               OR Variable_name = \'Innodb_page_size\';';
        $status = PMA_DBI_fetch_result($sql, 0, 1);

        $output = '<table class="data" id="table_innodb_bufferpool_usage">' . "\n"
                . '    <caption class="tblHeaders">' . "\n"
                . '        ' . $GLOBALS['strBufferPoolUsage'] . "\n"
                . '    </caption>' . "\n"
                . '    <tfoot>' . "\n"
                . '        <tr>' . "\n"
                . '            <th colspan="2">' . "\n"
                . '                ' . $GLOBALS['strTotalUC'] . "\n"
                . '                : ' . PMA_formatNumber(
                        $status['Innodb_buffer_pool_pages_total'], 0)
                . '&nbsp;' . $GLOBALS['strInnoDBPages']
                . ' / '
                . join('&nbsp;',
                    PMA_formatByteDown($status['Innodb_buffer_pool_pages_total'] * $status['Innodb_page_size'])) . "\n"
                . '            </th>' . "\n"
                . '        </tr>' . "\n"
                . '    </tfoot>' . "\n"
                . '    <tbody>' . "\n"
                . '        <tr class="odd">' . "\n"
                . '            <th>' . $GLOBALS['strFreePages'] . '</th>' . "\n"
                . '            <td class="value">'
                . PMA_formatNumber($status['Innodb_buffer_pool_pages_free'], 0)
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '        <tr class="even">' . "\n"
                . '            <th>' . $GLOBALS['strDirtyPages'] . '</th>' . "\n"
                . '            <td class="value">'
                . PMA_formatNumber($status['Innodb_buffer_pool_pages_dirty'], 0)
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '        <tr class="odd">' . "\n"
                . '            <th>' . $GLOBALS['strDataPages'] . '</th>' . "\n"
                . '            <td class="value">'
                . PMA_formatNumber($status['Innodb_buffer_pool_pages_data'], 0) . "\n"
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '        <tr class="even">' . "\n"
                . '            <th>' . $GLOBALS['strPagesToBeFlushed'] . '</th>' . "\n"
                . '            <td class="value">'
                . PMA_formatNumber($status['Innodb_buffer_pool_pages_flushed'], 0) . "\n"
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '        <tr class="odd">' . "\n"
                . '            <th>' . $GLOBALS['strBusyPages'] . '</th>' . "\n"
                . '            <td class="value">'
                . PMA_formatNumber($status['Innodb_buffer_pool_pages_misc'], 0) . "\n"
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '        <tr class="even">' . "\n"
                . '            <th>' . $GLOBALS['strLatchedPages'] . '</th>' . "\n"
                . '            <td class="value">'
                . PMA_formatNumber($status['Innodb_buffer_pool_pages_latched'], 0) . "\n"
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '    </tbody>' . "\n"
                . '</table>' . "\n\n"
                . '<table class="data" id="table_innodb_bufferpool_activity">' . "\n"
                . '    <caption class="tblHeaders">' . "\n"
                . '        ' . $GLOBALS['strBufferPoolActivity'] . "\n"
                . '    </caption>' . "\n"
                . '    <tbody>' . "\n"
                . '        <tr class="odd">' . "\n"
                . '            <th>' . $GLOBALS['strReadRequests'] . '</th>' . "\n"
                . '            <td class="value">'
                . PMA_formatNumber($status['Innodb_buffer_pool_read_requests'], 0) . "\n"
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '        <tr class="even">' . "\n"
                . '            <th>' . $GLOBALS['strWriteRequests'] . '</th>' . "\n"
                . '            <td class="value">'
                . PMA_formatNumber($status['Innodb_buffer_pool_write_requests'], 0) . "\n"
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '        <tr class="odd">' . "\n"
                . '            <th>' . $GLOBALS['strBufferReadMisses'] . '</th>' . "\n"
                . '            <td class="value">'
                . PMA_formatNumber($status['Innodb_buffer_pool_reads'], 0) . "\n"
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '        <tr class="even">' . "\n"
                . '            <th>' . $GLOBALS['strBufferWriteWaits'] . '</th>' . "\n"
                . '            <td class="value">'
                . PMA_formatNumber($status['Innodb_buffer_pool_wait_free'], 0) . "\n"
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '        <tr class="odd">' . "\n"
                . '            <th>' . $GLOBALS['strBufferReadMissesInPercent'] . '</th>' . "\n"
                . '            <td class="value">'
                . ($status['Innodb_buffer_pool_read_requests'] == 0
                    ? '---'
                    : htmlspecialchars(PMA_formatNumber($status['Innodb_buffer_pool_reads'] * 100 / $status['Innodb_buffer_pool_read_requests'], 3, 2)) . ' %') . "\n"
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '        <tr class="even">' . "\n"
                . '            <th>' . $GLOBALS['strBufferWriteWaitsInPercent'] . '</th>' . "\n"
                . '            <td class="value">'
                . ($status['Innodb_buffer_pool_write_requests'] == 0
                    ? '---'
                    : htmlspecialchars(PMA_formatNumber($status['Innodb_buffer_pool_wait_free'] * 100 / $status['Innodb_buffer_pool_write_requests'], 3, 2)) . ' %') . "\n"
                . '</td>' . "\n"
                . '        </tr>' . "\n"
                . '    </tbody>' . "\n"
                . '</table>' . "\n";
        return $output;
    }

    /**
     * returns InnoDB status
     *
     * @uses    htmlspecialchars()
     * @uses    PMA_DBI_fetch_value()
     * @return  string  result of SHOW INNODB STATUS inside pre tags
     */
    function getPageStatus()
    {
        return '<pre id="pre_innodb_status">' . "\n"
            . htmlspecialchars(PMA_DBI_fetch_value('SHOW INNODB STATUS;')) . "\n"
            . '</pre>' . "\n";
    }

    /**
     * returns content for page $id
     *
     * @uses    $this->getInfoPages()
     * @uses    array_key_exists()
     * @param   string  $id page id
     * @return  string  html output
     */
    function getPage($id)
    {
        if (! array_key_exists($id, $this->getInfoPages())) {
            return false;
        }

        $id = 'getPage' . $id;

        return $this->$id();
    }

    /**
     * returns string with filename for the MySQL helppage
     * about this storage engne
     *
     * @return  string  mysql helppage filename
     */
    function getMysqlHelpPage()
    {
        return 'innodb';
    }
}

?>
