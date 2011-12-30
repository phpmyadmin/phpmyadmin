<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build MediaWiki dumps of tables
 *
 * @package PhpMyAdmin-Export
 * @subpackage MediaWiki
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

if (isset($plugin_list)) {
    $plugin_list['mediawiki'] = array(
        'text' => __('MediaWiki Table'),
        'extension' => 'txt',
        'mime_type' => 'text/plain',
        'options' => array(
            array('type' => 'begin_group', 'name' => 'general_opts'),
            array('type' => 'hidden', 'name' => 'structure_or_data'),
            array('type' => 'end_group')
            ),
        'options_text' => __('Options'),
        );
} else {

    /**
     * Outputs export footer
     *
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportFooter() {
        return true;
    }

    /**
     * Outputs export header
     *
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportHeader() {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string  $db Database name
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportDBHeader($db) {
        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string  $db Database name
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportDBFooter($db) {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string  $db Database name
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportDBCreate($db) {
        return true;
    }

    /**
     * Outputs the content of a table in MediaWiki format
     *
     * @param string  $db         database name
     * @param string  $table      table name
     * @param string  $crlf       the end of line sequence
     * @param string  $error_url  the url to go back in case of error
     * @param string  $sql_query  SQL query for obtaining data
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportData($db, $table, $crlf, $error_url, $sql_query) {
        $columns = PMA_DBI_get_columns($db, $table);
        $columns = array_values($columns);
        $row_cnt = count($columns);

        $output = "{| cellpadding=\"10\" cellspacing=\"0\" border=\"1\" style=\"text-align:center;\"\n";
        $output .= "|+'''" . $table . "'''\n";
        $output .= "|- style=\"background:#ffdead;\"\n";
        $output .= "! style=\"background:#ffffff\" | \n";
        for ($i = 0; $i < $row_cnt; ++$i) {
            $output .= " | " . $columns[$i]['Field'];
            if (($i + 1) != $row_cnt) {
                $output .= "\n";
            }
        }
        $output .= "\n";

        $output .= "|- style=\"background:#f9f9f9;\"\n";
        $output .= "! style=\"background:#f2f2f2\" | Type\n";
        for ($i = 0; $i < $row_cnt; ++$i) {
            $output .= " | " . $columns[$i]['Type'];
            if (($i + 1) != $row_cnt) {
                $output .= "\n";
            }
        }
        $output .= "\n";

        $output .= "|- style=\"background:#f9f9f9;\"\n";
        $output .= "! style=\"background:#f2f2f2\" | Null\n";
        for ($i = 0; $i < $row_cnt; ++$i) {
            $output .= " | " . $columns[$i]['Null'];
            if (($i + 1) != $row_cnt) {
                $output .= "\n";
            }
        }
        $output .= "\n";

        $output .= "|- style=\"background:#f9f9f9;\"\n";
        $output .= "! style=\"background:#f2f2f2\" | Default\n";
        for ($i = 0; $i < $row_cnt; ++$i) {
            $output .= " | " . $columns[$i]['Default'];
            if (($i + 1) != $row_cnt) {
                $output .= "\n";
            }
        }
        $output .= "\n";

        $output .= "|- style=\"background:#f9f9f9;\"\n";
        $output .= "! style=\"background:#f2f2f2\" | Extra\n";
        for ($i = 0; $i < $row_cnt; ++$i) {
            $output .= " | " . $columns[$i]['Extra'];
            if (($i + 1) != $row_cnt) {
                $output .= "\n";
            }
        }
        $output .= "\n";

        $output .= "|}\n\n\n\n";
        return PMA_exportOutputHandler($output);
    }

}
?>
