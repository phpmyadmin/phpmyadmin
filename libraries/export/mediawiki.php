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
    function PMA_exportFooter()
    {
        return true;
    }

    /**
     * Outputs export header
     *
     * @return  bool        Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportHeader()
    {
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
    function PMA_exportDBHeader($db)
    {
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
    function PMA_exportDBFooter($db)
    {
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
    function PMA_exportDBCreate($db)
    {
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
     * @return  bool              Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportData($db, $table, $crlf, $error_url, $sql_query)
    {
        // Begin the table construction
        // Use the "wikitable" class for style
        // Use the "sortable"  class for allowing tables to be sorted by column
        $output  = "{| class=\"wikitable sortable\" style=\"text-align:center;\"\n";
        // Add the table caption
        $output .= "|+'''" . $table . "'''\n";

        // Get column names
        $column_names = PMA_DBI_get_column_names($db, $table);

        // Add column names as table headers
        if ( ! is_null($column_names) ) {
            // Use '|-' for separating rows
            $output .= "|-\n";

            // Use '!' for separating table headers
            foreach ($column_names as $column) {
                $output .= " ! " . $column . "\n";
            }
        }

        // Get the table data from the database
        $result = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
        $fields_cnt = PMA_DBI_num_fields($result);

        while ($row = PMA_DBI_fetch_row($result)) {
            $output .= "|-\n";

            // Use '|' for separating table columns
            for ($i = 0; $i < $fields_cnt; ++ $i) {
                $output .= " | " . $row[$i] . "\n";
            }
        }

        // End table construction
        $output .= "|}\n\n";
        return PMA_exportOutputHandler($output);
    }
}
?>
