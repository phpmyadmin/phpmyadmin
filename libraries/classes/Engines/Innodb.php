<?php
/**
 * The InnoDB storage engine
 */

declare(strict_types=1);

namespace PhpMyAdmin\Engines;

use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Util;
use function htmlspecialchars;
use function implode;

/**
 * The InnoDB storage engine
 */
class Innodb extends StorageEngine
{
    /**
     * Returns array with variable names related to InnoDB storage engine
     *
     * @return array   variable names
     */
    public function getVariables()
    {
        return [
            'innodb_data_home_dir'            => [
                'title' => __('Data home directory'),
                'desc'  => __(
                    'The common part of the directory path for all InnoDB data '
                    . 'files.'
                ),
            ],
            'innodb_data_file_path'           => [
                'title' => __('Data files'),
            ],
            'innodb_autoextend_increment'     => [
                'title' => __('Autoextend increment'),
                'desc'  => __(
                    'The increment size for extending the size of an autoextending '
                    . 'tablespace when it becomes full.'
                ),
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ],
            'innodb_buffer_pool_size'         => [
                'title' => __('Buffer pool size'),
                'desc'  => __(
                    'The size of the memory buffer InnoDB uses to cache data and '
                    . 'indexes of its tables.'
                ),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ],
            'innodb_additional_mem_pool_size' => [
                'title' => 'innodb_additional_mem_pool_size',
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ],
            'innodb_buffer_pool_awe_mem_mb'   => ['type' => PMA_ENGINE_DETAILS_TYPE_SIZE],
            'innodb_checksums'                => [],
            'innodb_commit_concurrency'       => [],
            'innodb_concurrency_tickets'      => ['type' => PMA_ENGINE_DETAILS_TYPE_NUMERIC],
            'innodb_doublewrite'              => [],
            'innodb_fast_shutdown'            => [],
            'innodb_file_io_threads'          => ['type' => PMA_ENGINE_DETAILS_TYPE_NUMERIC],
            'innodb_file_per_table'           => [],
            'innodb_flush_log_at_trx_commit'  => [],
            'innodb_flush_method'             => [],
            'innodb_force_recovery'           => [],
            'innodb_lock_wait_timeout'        => ['type' => PMA_ENGINE_DETAILS_TYPE_NUMERIC],
            'innodb_locks_unsafe_for_binlog'  => [],
            'innodb_log_arch_dir'             => [],
            'innodb_log_archive'              => [],
            'innodb_log_buffer_size'          => ['type' => PMA_ENGINE_DETAILS_TYPE_SIZE],
            'innodb_log_file_size'            => ['type' => PMA_ENGINE_DETAILS_TYPE_SIZE],
            'innodb_log_files_in_group'       => ['type' => PMA_ENGINE_DETAILS_TYPE_NUMERIC],
            'innodb_log_group_home_dir'       => [],
            'innodb_max_dirty_pages_pct'      => ['type' => PMA_ENGINE_DETAILS_TYPE_NUMERIC],
            'innodb_max_purge_lag'            => [],
            'innodb_mirrored_log_groups'      => ['type' => PMA_ENGINE_DETAILS_TYPE_NUMERIC],
            'innodb_open_files'               => ['type' => PMA_ENGINE_DETAILS_TYPE_NUMERIC],
            'innodb_support_xa'               => [],
            'innodb_sync_spin_loops'          => ['type' => PMA_ENGINE_DETAILS_TYPE_NUMERIC],
            'innodb_table_locks'              => ['type' => PMA_ENGINE_DETAILS_TYPE_BOOLEAN],
            'innodb_thread_concurrency'       => ['type' => PMA_ENGINE_DETAILS_TYPE_NUMERIC],
            'innodb_thread_sleep_delay'       => ['type' => PMA_ENGINE_DETAILS_TYPE_NUMERIC],
        ];
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
            return [];
        }
        $pages = [];
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
        global $dbi;

        // The following query is only possible because we know
        // that we are on MySQL 5 here (checked above)!
        // side note: I love MySQL 5 for this. :-)
        $sql = 'SHOW STATUS'
            . ' WHERE Variable_name LIKE \'Innodb\\_buffer\\_pool\\_%\''
            . ' OR Variable_name = \'Innodb_page_size\';';
        $status = $dbi->fetchResult($sql, 0, 1);

        $output = '<table class="table table-light table-striped table-hover w-auto float-left">' . "\n"
            . '    <caption>' . "\n"
            . '        ' . __('Buffer Pool Usage') . "\n"
            . '    </caption>' . "\n"
            . '    <tfoot class="thead-light">' . "\n"
            . '        <tr>' . "\n"
            . '            <th colspan="2">' . "\n"
            . '                ' . __('Total:') . ' '
            . Util::formatNumber(
                $status['Innodb_buffer_pool_pages_total'],
                0
            )
            . '&nbsp;' . __('pages')
            . ' / '
            . implode(
                '&nbsp;',
                Util::formatByteDown(
                    $status['Innodb_buffer_pool_pages_total']
                    * $status['Innodb_page_size']
                )
            ) . "\n"
            . '            </th>' . "\n"
            . '        </tr>' . "\n"
            . '    </tfoot>' . "\n"
            . '    <tbody>' . "\n"
            . '        <tr>' . "\n"
            . '            <th scope="row">' . __('Free pages') . '</th>' . "\n"
            . '            <td class="text-monospace text-right">'
            . Util::formatNumber(
                $status['Innodb_buffer_pool_pages_free'],
                0
            )
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr>' . "\n"
            . '            <th scope="row">' . __('Dirty pages') . '</th>' . "\n"
            . '            <td class="text-monospace text-right">'
            . Util::formatNumber(
                $status['Innodb_buffer_pool_pages_dirty'],
                0
            )
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr>' . "\n"
            . '            <th scope="row">' . __('Pages containing data') . '</th>' . "\n"
            . '            <td class="text-monospace text-right">'
            . Util::formatNumber(
                $status['Innodb_buffer_pool_pages_data'],
                0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr>' . "\n"
            . '            <th scope="row">' . __('Pages to be flushed') . '</th>' . "\n"
            . '            <td class="text-monospace text-right">'
            . Util::formatNumber(
                $status['Innodb_buffer_pool_pages_flushed'],
                0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr>' . "\n"
            . '            <th scope="row">' . __('Busy pages') . '</th>' . "\n"
            . '            <td class="text-monospace text-right">'
            . Util::formatNumber(
                $status['Innodb_buffer_pool_pages_misc'],
                0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>';

        // not present at least since MySQL 5.1.40
        if (isset($status['Innodb_buffer_pool_pages_latched'])) {
            $output .= '        <tr>'
                . '            <th scope="row">' . __('Latched pages') . '</th>'
                . '            <td class="text-monospace text-right">'
                . Util::formatNumber(
                    $status['Innodb_buffer_pool_pages_latched'],
                    0
                )
                . '</td>'
                . '        </tr>';
        }

        $output .= '    </tbody>' . "\n"
            . '</table>' . "\n\n"
            . '<table class="table table-light table-striped table-hover w-auto ml-4 float-left">' . "\n"
            . '    <caption>' . "\n"
            . '        ' . __('Buffer Pool Activity') . "\n"
            . '    </caption>' . "\n"
            . '    <tbody>' . "\n"
            . '        <tr>' . "\n"
            . '            <th scope="row">' . __('Read requests') . '</th>' . "\n"
            . '            <td class="text-monospace text-right">'
            . Util::formatNumber(
                $status['Innodb_buffer_pool_read_requests'],
                0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr>' . "\n"
            . '            <th scope="row">' . __('Write requests') . '</th>' . "\n"
            . '            <td class="text-monospace text-right">'
            . Util::formatNumber(
                $status['Innodb_buffer_pool_write_requests'],
                0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr>' . "\n"
            . '            <th scope="row">' . __('Read misses') . '</th>' . "\n"
            . '            <td class="text-monospace text-right">'
            . Util::formatNumber(
                $status['Innodb_buffer_pool_reads'],
                0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr>' . "\n"
            . '            <th scope="row">' . __('Write waits') . '</th>' . "\n"
            . '            <td class="text-monospace text-right">'
            . Util::formatNumber(
                $status['Innodb_buffer_pool_wait_free'],
                0
            ) . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr>' . "\n"
            . '            <th scope="row">' . __('Read misses in %') . '</th>' . "\n"
            . '            <td class="text-monospace text-right">'
            . ($status['Innodb_buffer_pool_read_requests'] == 0
                ? '---'
                : htmlspecialchars(
                    Util::formatNumber(
                        $status['Innodb_buffer_pool_reads'] * 100
                        / $status['Innodb_buffer_pool_read_requests'],
                        3,
                        2
                    )
                ) . ' %') . "\n"
            . '</td>' . "\n"
            . '        </tr>' . "\n"
            . '        <tr>' . "\n"
            . '            <th scope="row">' . __('Write waits in %') . '</th>' . "\n"
            . '            <td class="text-monospace text-right">'
            . ($status['Innodb_buffer_pool_write_requests'] == 0
                ? '---'
                : htmlspecialchars(
                    Util::formatNumber(
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
        global $dbi;

        return '<pre id="pre_innodb_status">' . "\n"
            . htmlspecialchars((string) $dbi->fetchValue(
                'SHOW ENGINE INNODB STATUS;',
                0,
                'Status'
            )) . "\n" . '</pre>' . "\n";
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
     * @return string the version number, or empty if not running as a plugin
     */
    public function getInnodbPluginVersion()
    {
        global $dbi;

        return $dbi->fetchValue('SELECT @@innodb_version;');
    }

    /**
     * Gets the InnoDB file format
     *
     * (do not confuse this with phpMyAdmin's storage engine plugins!)
     *
     * @return string|null the InnoDB file format
     */
    public function getInnodbFileFormat(): ?string
    {
        global $dbi;

        $value = $dbi->fetchValue(
            "SHOW GLOBAL VARIABLES LIKE 'innodb_file_format';",
            0,
            1
        );

        if ($value === false) {
            // This variable does not exist anymore on MariaDB >= 10.6.0
            // This variable does not exist anymore on MySQL >= 8.0.0
            return null;
        }

        return (string) $value;
    }

    /**
     * Verifies if this server supports the innodb_file_per_table feature
     *
     * (do not confuse this with phpMyAdmin's storage engine plugins!)
     *
     * @return bool whether this feature is supported or not
     */
    public function supportsFilePerTable()
    {
        global $dbi;

        return $dbi->fetchValue(
            "SHOW GLOBAL VARIABLES LIKE 'innodb_file_per_table';",
            0,
            1
        ) === 'ON';
    }
}
