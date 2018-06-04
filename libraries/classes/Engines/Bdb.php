<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The BDB storage engine
 *
 * @package PhpMyAdmin-Engines
 */
declare(strict_types=1);

namespace PhpMyAdmin\Engines;

use PhpMyAdmin\StorageEngine;

/**
 * The BDB storage engine
 *
 * @package PhpMyAdmin-Engines
 */
class Bdb extends StorageEngine
{
    /**
     * Returns array with variable names related to this storage engine
     *
     * @return array   variable names
     */
    public function getVariables()
    {
        return [
            'version_bdb'         => [
                'title' => __('Version information'),
            ],
            'bdb_cache_size'      => [
                'type' => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ],
            'bdb_home'            => [],
            'bdb_log_buffer_size' => [
                'type' => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ],
            'bdb_logdir'          => [],
            'bdb_max_lock'        => [
                'type' => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ],
            'bdb_shared_data'     => [],
            'bdb_tmpdir'          => [],
            'bdb_data_direct'     => [],
            'bdb_lock_detect'     => [],
            'bdb_log_direct'      => [],
            'bdb_no_recover'      => [],
            'bdb_no_sync'         => [],
            'skip_sync_bdb_logs'  => [],
            'sync_bdb_logs'       => [],
        ];
    }

    /**
     * Returns the pattern to be used in the query for SQL variables
     * related to this storage engine
     *
     * @return string LIKE pattern
     */
    public function getVariablesLikePattern()
    {
        return '%bdb%';
    }

    /**
     * returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    public function getMysqlHelpPage()
    {
        return 'bdb';
    }
}
