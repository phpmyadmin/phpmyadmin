<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *  Set of functions used to build MediaWiki dumps of tables
 *
 *  @package PhpMyAdmin-Export
 *  @subpackage MediaWiki
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

if (isset($plugin_list)) {
    $plugin_list['mediawiki'] = array(
        'text' => __('MediaWiki Table'),
        'extension' => 'mediawiki',
        'mime_type' => 'text/plain',
        'options' => array(
            ),
        'options_text' => __('Options'),
        );

        // general options
        $plugin_list['mediawiki']['options'][] = array(
            'type' => 'begin_group',
            'name' => 'general_opts');

        // what to dump (structure/data/both)
        $plugin_list['mediawiki']['options'][] = array(
            'type' => 'begin_subgroup',
            'subgroup_header' => array(
                'type' => 'message_only',
                'text' => __('Dump table')
            ));
        $plugin_list['mediawiki']['options'][] = array(
            'type' => 'radio',
            'name' => 'structure_or_data',
            'values' => array(
                'structure' => __('structure'),
                'data' => __('data'),
                'structure_and_data' => __('structure and data')
            ));
        $plugin_list['mediawiki']['options'][] = array(
            'type' => 'end_subgroup'
            );

        // export table name
        $plugin_list['mediawiki']['options'][] = array(
            'type' => 'bool',
            'name' => 'caption',
            'text' => __('Export table names')
            );

        // export table headers
        $plugin_list['mediawiki']['options'][] = array(
            'type' => 'bool',
            'name' => 'headers',
            'text' => __('Export table headers')
            );

        // end general options
        $plugin_list['mediawiki']['options'][] = array(
            'type' => 'end_group'
            );
} else {

    /**
     * Outputs comments containing info about the exported tables
     *
     * @param string $text Text of comment
     *
     * @return string The formatted comment
     *
     * @access private
     */
    function PMA_exportComment($text = '')
    {
        // see http://www.mediawiki.org/wiki/Help:Formatting
        $comment = PMA_exportCRLF();
        $comment .= '<!--' . PMA_exportCRLF();
        $comment .= $text  . PMA_exportCRLF();
        $comment .= '-->'  . str_repeat(PMA_exportCRLF(), 2);

        return $comment;
    }

    /**
     * Outputs CRLF
     *
     * @return string CRLF
     *
     * @access private
     */
    function PMA_exportCRLF()
    {
        // The CRLF expected by the mediawiki format is "\n"
        return "\n";
    }

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
     * @param string $db Database name
     *
     * @return  bool     Whether it succeeded
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
     * @param string $db Database name
     *
     * @return  bool     Whether it succeeded
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
     * @param string $db Database name
     *
     * @return  bool     Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportDBCreate($db)
    {
        return true;
    }

    /**
     * Outputs table's structure
     *
     * @param string $db          database name
     * @param string $table       table name
     * @param string $crlf        the end of line sequence
     * @param string $error_url   the url to go back in case of error
     * 
     * @param bool   $relation    whether to include relation comments
     * @param bool   $comments    whether to include the pmadb-style column comments
     *                            as comments in the structure; this is deprecated
     *                            but the parameter is left here because export.php
     *                            calls PMA_exportStructure() also for other export
     *                            types which use this parameter
     * @param bool   $mime        whether to include mime comments
     * @param bool   $dates       whether to include creation/update/check dates
     * @param string $export_mode 'create_table','triggers','create_view','stand_in'
     * @param string $export_type 'server', 'database', 'table'
     *
     * @return bool               Whether it succeeded
     *
     * @access public
     */
    function PMA_exportStructure(
        $db, 
        $table, 
        $crlf, 
        $error_url, 
        $relation = false, 
        $comments = false, 
        $mime = false, 
        $dates = false, 
        $export_mode, 
        $export_type
    ) {
        switch($export_mode) {
        case 'create_table':
            $columns = PMA_DBI_get_columns($db, $table);
            $columns = array_values($columns);
            $row_cnt = count($columns);

            // Print structure comment
            $output = PMA_exportComment(
                "Table structure for "
                . PMA_backquote($table)
            );

            // Begin the table construction
            $output .= "{| class=\"wikitable\" style=\"text-align:center;\"" 
                     . PMA_exportCRLF();

            // Add the table name
            if ($GLOBALS['mediawiki_caption']) {
                $output .= "|+'''" . $table . "'''" . PMA_exportCRLF();
            }

            // Add the table headers
            if ($GLOBALS['mediawiki_headers']) {
                $output .= "|- style=\"background:#ffdead;\"" . PMA_exportCRLF();
                $output .= "! style=\"background:#ffffff\" | " . PMA_exportCRLF();
                for ($i = 0; $i < $row_cnt; ++$i) {
                    $output .= " | " . $columns[$i]['Field']. PMA_exportCRLF();
                }
            }

            // Add the table structure
            $output .= "|-" .  PMA_exportCRLF();
            $output .= "! Type" . PMA_exportCRLF();
            for ($i = 0; $i < $row_cnt; ++$i) {
                $output .= " | " . $columns[$i]['Type'] . PMA_exportCRLF();
            }

            $output .= "|-" .  PMA_exportCRLF();
            $output .= "! Null" . PMA_exportCRLF();
            for ($i = 0; $i < $row_cnt; ++$i) {
                $output .= " | " . $columns[$i]['Null'] . PMA_exportCRLF();
            }

            $output .= "|-" .  PMA_exportCRLF();
            $output .= "! Default" . PMA_exportCRLF();
            for ($i = 0; $i < $row_cnt; ++$i) {
                $output .= " | " . $columns[$i]['Default'] . PMA_exportCRLF();
            }

            $output .= "|-" .  PMA_exportCRLF();
            $output .= "! Extra" . PMA_exportCRLF();
            for ($i = 0; $i < $row_cnt; ++$i) {
                $output .= " | " . $columns[$i]['Extra'] . PMA_exportCRLF();
            }

            $output .= "|}" .  str_repeat(PMA_exportCRLF(), 2);
            break;
        } // end switch

        return PMA_exportOutputHandler($output);
    }

    /**
     * Outputs the content of a table in MediaWiki format
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     *
     * @return  bool             Whether it succeeded
     *
     * @access  public
     */
    function PMA_exportData(
        $db, 
        $table, 
        $crlf, 
        $error_url, 
        $sql_query
    ) {
        // Print data comment
        $output = PMA_exportComment("Table data for ". PMA_backquote($table));

        // Begin the table construction
        // Use the "wikitable" class for style
        // Use the "sortable"  class for allowing tables to be sorted by column
        $output  .= "{| class=\"wikitable sortable\" style=\"text-align:center;\"" 
                  . PMA_exportCRLF();

        // Add the table name
        if ($GLOBALS['mediawiki_caption']) {
            $output .= "|+'''" . $table . "'''" . PMA_exportCRLF();
        }

        // Add the table headers
        if ($GLOBALS['mediawiki_headers']) {
            // Get column names
            $column_names = PMA_DBI_get_column_names($db, $table);

            // Add column names as table headers
            if ( ! is_null($column_names) ) {
                // Use '|-' for separating rows
                $output .= "|-" . PMA_exportCRLF();

                // Use '!' for separating table headers
                foreach ($column_names as $column) {
                    $output .= " ! " . $column . "" . PMA_exportCRLF();
                }
            }
        }

        // Get the table data from the database
        $result = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
        $fields_cnt = PMA_DBI_num_fields($result);

        while ($row = PMA_DBI_fetch_row($result)) {
            $output .= "|-" . PMA_exportCRLF();

            // Use '|' for separating table columns
            for ($i = 0; $i < $fields_cnt; ++ $i) {
                $output .= " | " . $row[$i] . "" . PMA_exportCRLF();
            }
        }

        // End table construction
        $output .= "|}" . str_repeat(PMA_exportCRLF(), 2);
        return PMA_exportOutputHandler($output);
    }
}
?>