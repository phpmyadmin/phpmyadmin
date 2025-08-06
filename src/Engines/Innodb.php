<?php

declare(strict_types=1);

namespace PhpMyAdmin\Engines;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Engines\Innodb\BufferPool;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;

use function __;
use function htmlspecialchars;

/**
 * The InnoDB storage engine
 */
class Innodb extends StorageEngine
{
    /**
     * Returns array with variable names related to InnoDB storage engine
     *
     * @return mixed[]   variable names
     */
    public function getVariables(): array
    {
        return [
            'innodb_data_home_dir' => [
                'title' => __('Data home directory'),
                'desc' => __('The common part of the directory path for all InnoDB data files.'),
            ],
            'innodb_data_file_path' => ['title' => __('Data files')],
            'innodb_autoextend_increment' => [
                'title' => __('Autoextend increment'),
                'desc' => __(
                    'The increment size for extending the size of an autoextending tablespace when it becomes full.',
                ),
                'type' => StorageEngine::DETAILS_TYPE_NUMERIC,
            ],
            'innodb_buffer_pool_size' => [
                'title' => __('Buffer pool size'),
                'desc' => __('The size of the memory buffer InnoDB uses to cache data and indexes of its tables.'),
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'innodb_additional_mem_pool_size' => [
                'title' => 'innodb_additional_mem_pool_size',
                'type' => StorageEngine::DETAILS_TYPE_SIZE,
            ],
            'innodb_buffer_pool_awe_mem_mb' => ['type' => StorageEngine::DETAILS_TYPE_SIZE],
            'innodb_checksums' => [],
            'innodb_commit_concurrency' => [],
            'innodb_concurrency_tickets' => ['type' => StorageEngine::DETAILS_TYPE_NUMERIC],
            'innodb_doublewrite' => [],
            'innodb_fast_shutdown' => [],
            'innodb_file_io_threads' => ['type' => StorageEngine::DETAILS_TYPE_NUMERIC],
            'innodb_file_per_table' => [],
            'innodb_flush_log_at_trx_commit' => [],
            'innodb_flush_method' => [],
            'innodb_force_recovery' => [],
            'innodb_lock_wait_timeout' => ['type' => StorageEngine::DETAILS_TYPE_NUMERIC],
            'innodb_locks_unsafe_for_binlog' => [],
            'innodb_log_arch_dir' => [],
            'innodb_log_archive' => [],
            'innodb_log_buffer_size' => ['type' => StorageEngine::DETAILS_TYPE_SIZE],
            'innodb_log_file_size' => ['type' => StorageEngine::DETAILS_TYPE_SIZE],
            'innodb_log_files_in_group' => ['type' => StorageEngine::DETAILS_TYPE_NUMERIC],
            'innodb_log_group_home_dir' => [],
            'innodb_max_dirty_pages_pct' => ['type' => StorageEngine::DETAILS_TYPE_NUMERIC],
            'innodb_max_purge_lag' => [],
            'innodb_mirrored_log_groups' => ['type' => StorageEngine::DETAILS_TYPE_NUMERIC],
            'innodb_open_files' => ['type' => StorageEngine::DETAILS_TYPE_NUMERIC],
            'innodb_support_xa' => [],
            'innodb_sync_spin_loops' => ['type' => StorageEngine::DETAILS_TYPE_NUMERIC],
            'innodb_table_locks' => ['type' => StorageEngine::DETAILS_TYPE_BOOLEAN],
            'innodb_thread_concurrency' => ['type' => StorageEngine::DETAILS_TYPE_NUMERIC],
            'innodb_thread_sleep_delay' => ['type' => StorageEngine::DETAILS_TYPE_NUMERIC],
        ];
    }

    /**
     * Returns the pattern to be used in the query for SQL variables
     * related to InnoDb storage engine
     *
     * @return string  SQL query LIKE pattern
     */
    public function getVariablesLikePattern(): string
    {
        return 'innodb\\_%';
    }

    /**
     * Get information pages
     *
     * @return string[] detail pages
     */
    public function getInfoPages(): array
    {
        if ($this->support < StorageEngine::SUPPORT_YES) {
            return [];
        }

        return ['Bufferpool' => __('Buffer Pool'), 'Status' => __('InnoDB Status')];
    }

    /**
     * returns html tables with stats over inno db buffer pool
     *
     * @return string  html table with stats
     */
    public function getPageBufferPool(): string
    {
        return (new Template())->render('server/engines/_innodb_buffer_pool', [
            'buffer_pool' => $this->getBufferPoolStatus(),
        ]);
    }

    /**
     * returns InnoDB status
     *
     * @return string  result of SHOW ENGINE INNODB STATUS inside pre tags
     */
    public function getPageStatus(): string
    {
        return '<pre id="pre_innodb_status">' . "\n"
            . htmlspecialchars((string) DatabaseInterface::getInstance()->fetchValue(
                'SHOW ENGINE INNODB STATUS;',
                'Status',
            )) . "\n" . '</pre>' . "\n";
    }

    /**
     * returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    public function getMysqlHelpPage(): string
    {
        return 'innodb-storage-engine';
    }

    /**
     * Gets the InnoDB plugin version number
     *
     * @return string the version number, or empty if not running as a plugin
     */
    public function getInnodbPluginVersion(): string
    {
        return (string) DatabaseInterface::getInstance()->fetchValue('SELECT @@innodb_version;');
    }

    /**
     * Gets the InnoDB file format
     *
     * (do not confuse this with phpMyAdmin's storage engine plugins!)
     *
     * @return string|null the InnoDB file format
     */
    public function getInnodbFileFormat(): string|null
    {
        $value = DatabaseInterface::getInstance()->fetchValue("SHOW GLOBAL VARIABLES LIKE 'innodb_file_format';", 1);

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
     */
    public function supportsFilePerTable(): bool
    {
        $dbi = DatabaseInterface::getInstance();

        return $dbi->fetchValue("SHOW GLOBAL VARIABLES LIKE 'innodb_file_per_table';", 1) === 'ON';
    }

    private function getBufferPoolStatus(): BufferPool
    {
        $result = DatabaseInterface::getInstance()->tryQuery(
            "SHOW STATUS WHERE Variable_name LIKE 'Innodb\\_buffer\\_pool\\_%' OR Variable_name = 'Innodb_page_size';",
            cacheAffectedRows: false,
        );

        return BufferPool::fromResult($result !== false ? $result->fetchAllKeyPair() : []);
    }
}
