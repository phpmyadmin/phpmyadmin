<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Engines
 */

/**
 * the PBMS daemon
 * @package PhpMyAdmin-Engines
 */
class PMA_StorageEngine_pbms extends PMA_StorageEngine
{
    /**
     * returns array with variable names dedicated to PBMS daemon
     *
     * @return  array   variable names
     */
    function engine_init()
    {
        $this->engine  = "PBMS";
        $this->title   = "PrimeBase Media Streaming Daemon";
        $this->comment = "Provides BLOB streaming service for storage engines,";
        $this->support = PMA_ENGINE_SUPPORT_YES;
    }

    function getVariables()
    {
        return array(
            'pbms_garbage_threshold' => array(
                'title' => __('Garbage Threshold'),
                'desc'  => __('The percentage of garbage in a repository file before it is compacted.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_PLAINTEXT
            ),
            'pbms_port' => array(
                'title' => __('Port'),
                'desc'  => __('The port for the PBMS stream-based communications. Setting this value to 0 will disable HTTP communication with the daemon.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_PLAINTEXT
            ),
            'pbms_repository_threshold' => array(
                'title' => __('Repository Threshold'),
                'desc'  => __('The maximum size of a BLOB repository file. You may use Kb, MB or GB to indicate the unit of the value. A value in bytes is assumed when no unit is specified.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_PLAINTEXT
            ),
            'pbms_temp_blob_timeout' => array(
                'title' => __('Temp Blob Timeout'),
                'desc'  => __('The timeout, in seconds, for temporary BLOBs. Uploaded BLOB data is removed after this time, unless they are referenced by a record in the database.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_PLAINTEXT
            ),
            'pbms_temp_log_threshold' => array(
                'title' => __('Temp Log Threshold'),
                'desc'  => __('The maximum size of a temporary BLOB log file. You may use Kb, MB or GB to indicate the unit of the value. A value in bytes is assumed when no unit is specified.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_PLAINTEXT
            ),
            'pbms_max_keep_alive' => array(
                'title' => __('Max Keep Alive'),
                'desc'  => __('The timeout for inactive connection with the keep-alive flag set. After this time the connection will be closed. The time-out is in milliseconds (1/1000).'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_PLAINTEXT
            ),
            'pbms_http_metadata_headers' => array(
                'title' => __('Metadata Headers'),
                'desc'  => __('A ":" delimited list of metadata headers to be used to initialize the pbms_metadata_header table when a database is created.'),
                'type'  => PMA_ENGINE_DETAILS_TYPE_PLAINTEXT
            ),
        );
    }

    //--------------------
    function getInfoPages()
    {
        $pages = array();
        $pages['Documentation'] = __('Documentation');
        return $pages;
    }

    //--------------------
    function getPage($id)
    {
        if (! array_key_exists($id, $this->getInfoPages())) {
            return false;
        }

        $id = 'getPage' . $id;

        return $this->$id();
    }

    function getPageConfigure()
    {
    }

    function getPageDocumentation()
    {
        $output = '<p>'
        . sprintf(__('Documentation and further information about PBMS can be found on %sThe PrimeBase Media Streaming home page%s.'), '<a href="' . PMA_linkURL('http://www.blobstreaming.org/') . '" target="_blank">', '</a>')
        . '</p>' . "\n"
        . '<h3>' . __('Related Links') . '</h3>' . "\n"
        . '<ul>' . "\n"
        . '<li><a href="' . PMA_linkURL('http://bpbdev.blogspot.com/') . '" target="_blank">' . __('The PrimeBase Media Streaming Blog by Barry Leslie') . '</a></li>' . "\n"
        . '<li><a href="' . PMA_linkURL('http://www.primebase.com/xt') . '" target="_blank">' . __('PrimeBase XT Home Page') . '</a></li>' . "\n"
        . '</ul>' . "\n";

        return $output;
    }
}

?>
