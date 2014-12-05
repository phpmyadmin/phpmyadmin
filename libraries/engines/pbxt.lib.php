<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The PBXT storage engine
 *
 * @package PhpMyAdmin-Engines
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * The PBXT storage engine
 *
 * @package PhpMyAdmin-Engines
 */
class PMA_StorageEngine_Pbxt extends PMA_StorageEngine
{
    /**
     * Returns array with variable names dedicated to PBXT storage engine
     *
     * @return array   variable names
     */
    public function getVariables()
    {
        return array(
            'pbxt_index_cache_size' => array(
                'title' => __('Index cache size'),
                'desc'  => __('This is the amount of memory allocated to the index cache. Default value is 32MB. The memory allocated here is used only for caching index pages.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_record_cache_size' => array(
                'title' => __('Record cache size'),
                'desc'  => __('This is the amount of memory allocated to the record cache used to cache table data. The default value is 32MB. This memory is used to cache changes to the handle data (.xtd) and row pointer (.xtr) files.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_log_cache_size' => array(
                'title' => __('Log cache size'),
                'desc'  => __('The amount of memory allocated to the transaction log cache used to cache on transaction log data. The default is 16MB.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_log_file_threshold' => array(
                'title' => __('Log file threshold'),
                'desc'  => __('The size of a transaction log before rollover, and a new log is created. The default value is 16MB.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_transaction_buffer_size' => array(
                'title' => __('Transaction buffer size'),
                'desc'  => __('The size of the global transaction log buffer (the engine allocates 2 buffers of this size). The default is 1MB.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_checkpoint_frequency' => array(
                'title' => __('Checkpoint frequency'),
                'desc'  => __('The amount of data written to the transaction log before a checkpoint is performed. The default value is 24MB.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_data_log_threshold' => array(
                'title' => __('Data log threshold'),
                'desc'  => __('The maximum size of a data log file. The default value is 64MB. PBXT can create a maximum of 32000 data logs, which are used by all tables. So the value of this variable can be increased to increase the total amount of data that can be stored in the database.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_garbage_threshold' => array(
                'title' => __('Garbage threshold'),
                'desc'  => __('The percentage of garbage in a data log file before it is compacted. This is a value between 1 and 99. The default is 50.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC
            ),
            'pbxt_log_buffer_size' => array(
                'title' => __('Log buffer size'),
                'desc'  => __('The size of the buffer used when writing a data log. The default is 256MB. The engine allocates one buffer per thread, but only if the thread is required to write a data log.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_data_file_grow_size' => array(
                'title' => __('Data file grow size'),
                'desc'  => __('The grow size of the handle data (.xtd) files.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_row_file_grow_size' => array(
                'title' => __('Row file grow size'),
                'desc'  => __('The grow size of the row pointer (.xtr) files.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
            ),
            'pbxt_log_file_count' => array(
                'title' => __('Log file count'),
                'desc'  => __('This is the number of transaction log files (pbxt/system/xlog*.xt) the system will maintain. If the number of logs exceeds this value then old logs will be deleted, otherwise they are renamed and given the next highest number.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC
            ),
        );
    }

    /**
     * returns the pbxt engine specific handling for
     * PMA_ENGINE_DETAILS_TYPE_SIZE variables.
     *
     * @param string $formatted_size the size expression (for example 8MB)
     *
     * @return string the formatted value and its unit
     */
    public function resolveTypeSize($formatted_size)
    {
        if (preg_match('/^[0-9]+[a-zA-Z]+$/', $formatted_size)) {
            $value = PMA_Util::extractValueFromFormattedSize($formatted_size);
        } else {
            $value = $formatted_size;
        }
        return PMA_Util::formatByteDown($value);
    }

    //--------------------
    /**
     * Get information about pages
     *
     * @return array Information about pages
     */
    public function getInfoPages()
    {
        $pages = array();
        $pages['Documentation'] = __('Documentation');
        return $pages;
    }

    //--------------------
    /**
     * Get content of documentation page
     *
     * @return string
     */
    public function getPageDocumentation()
    {
        $output = '<p>' . sprintf(
            __(
                'Documentation and further information about PBXT'
                . ' can be found on the %sPrimeBase XT Home Page%s.'
            ),
            '<a href="' . PMA_linkURL('http://www.primebase.com/xt/')
            . '" target="_blank">', '</a>'
        )
        . '</p>' . "\n"
        . '<h3>' . __('Related Links') . '</h3>' . "\n"
        . '<ul>' . "\n"
        . '<li><a href="' . PMA_linkURL('http://pbxt.blogspot.com/')
        . '" target="_blank">' . __('The PrimeBase XT Blog by Paul McCullagh')
        . '</a></li>' . "\n"
        . '</ul>' . "\n";

        return $output;
    }
}

?>
