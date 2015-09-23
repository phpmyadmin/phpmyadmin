<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The BDB storage engine
 *
 * @package PhpMyAdmin-Engines
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * The BDB storage engine
 *
 * @package PhpMyAdmin-Engines
 */
class PMA_StorageEngine_Bdb extends PMA_StorageEngine
{
    /**
     * Returns array with variable names related to this storage engine
     *
     * @return array   variable names
     */
    public function getVariables()
    {
        return array(
            'version_bdb' => array(
                'title' => __('Version information'),
            ),
            'bdb_cache_size' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'bdb_home' => array(
            ),
            'bdb_log_buffer_size' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'bdb_logdir' => array(
            ),
            'bdb_max_lock' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'bdb_shared_data' => array(
            ),
            'bdb_tmpdir' => array(
            ),
            'bdb_data_direct' => array(
            ),
            'bdb_lock_detect' => array(
            ),
            'bdb_log_direct' => array(
            ),
            'bdb_no_recover' => array(
            ),
            'bdb_no_sync' => array(
            ),
            'skip_sync_bdb_logs' => array(
            ),
            'sync_bdb_logs' => array(
            ),
        );
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

