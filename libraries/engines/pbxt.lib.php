<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id$
 * @package phpMyAdmin-Engines
 */

/**
 * the MyISAM storage engine
 * @package phpMyAdmin-Engines
 */
class PMA_StorageEngine_pbxt extends PMA_StorageEngine
{
    /**
     * returns array with variable names dedicated to PBXT storage engine
     *
     * @return  array   variable names
     */
    function getVariables()
    {
        return array(
            'pbxt_index_cache_size' => array(
                'title' => $GLOBALS['strPBXTIndexCacheSize'],
                'desc'  => $GLOBALS['strPBXTIndexCacheSizeDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_record_cache_size' => array(
                'title' => $GLOBALS['strPBXTRecordCacheSize'],
                'desc'  => $GLOBALS['strPBXTRecordCacheSizeDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_log_cache_size' => array(
                'title' => $GLOBALS['strPBXTLogCacheSize'],
                'desc'  => $GLOBALS['strPBXTLogCacheSizeDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_log_file_threshold' => array(
                'title' => $GLOBALS['strPBXTLogFileThreshold'],
                'desc'  => $GLOBALS['strPBXTLogFileThresholdDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_transaction_buffer_size' => array(
                'title' => $GLOBALS['strPBXTTransactionBufferSize'],
                'desc'  => $GLOBALS['strPBXTTransactionBufferSizeDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_checkpoint_frequency' => array(
                'title' => $GLOBALS['strPBXTCheckpointFrequency'],
                'desc'  => $GLOBALS['strPBXTCheckpointFrequencyDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_data_log_threshold' => array(
                'title' => $GLOBALS['strPBXTDataLogThreshold'],
                'desc'  => $GLOBALS['strPBXTDataLogThresholdDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_garbage_threshold' => array(
                'title' => $GLOBALS['strPBXTGarbageThreshold'],
                'desc'  => $GLOBALS['strPBXTGarbageThresholdDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC
            ),
            'pbxt_log_buffer_size' => array(
                'title' => $GLOBALS['strPBXTLogBufferSize'],
                'desc'  => $GLOBALS['strPBXTLogBufferSizeDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_data_file_grow_size' => array(
                'title' => $GLOBALS['strPBXTDataFileGrowSize'],
                'desc'  => $GLOBALS['strPBXTDataFileGrowSizeDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_row_file_grow_size' => array(
                'title' => $GLOBALS['strPBXTRowFileGrowSize'],
                'desc'  => $GLOBALS['strPBXTRowFileGrowSizeDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_log_file_count' => array(
                'title' => $GLOBALS['strPBXTLogFileCount'],
                'desc'  => $GLOBALS['strPBXTLogFileCountDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC
            ),
        );
    }
}

?>
