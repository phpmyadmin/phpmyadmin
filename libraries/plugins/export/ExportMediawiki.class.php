<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build MediaWiki dumps of tables
 *
 * @package    PhpMyAdmin-Export
 * @subpackage MediaWiki
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the export interface */
require_once "libraries/plugins/ExportPlugin.class.php";

/**
 * Handles the export for the MediaWiki class
 *
 * @todo add descriptions for all vars/methods
 * @package PhpMyAdmin-Export
 */
class ExportMediawiki extends ExportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Sets the export MediaWiki properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $this->properties = array(
            'text' => __('MediaWiki Table'),
            'extension' => 'mediawiki',
            'mime_type' => 'text/plain',
            'options' => array(),
            'options_text' => __('Options')
        );

        // general options
        $this->properties['options'][] = array(
            'type' => 'begin_group',
            'name' => 'general_opts'
        );

        // what to dump (structure/data/both)
        $this->properties['options'][] = array(
            'type' => 'begin_subgroup',
            'subgroup_header' => array(
                'type' => 'message_only',
                'text' => __('Dump table')
            )
        );
        $this->properties['options'][] = array(
            'type' => 'radio',
            'name' => 'structure_or_data',
            'values' => array(
                'structure' => __('structure'),
                'data' => __('data'),
                'structure_and_data' => __('structure and data')
            )
        );
        $this->properties['options'][] = array(
            'type' => 'end_subgroup'
        );

        // export table name
        $this->properties['options'][] = array(
            'type' => 'bool',
            'name' => 'caption',
            'text' => __('Export table names')
        );

        // export table headers
        $this->properties['options'][] = array(
            'type' => 'bool',
            'name' => 'headers',
            'text' => __('Export table headers')
        );

        // end general options
        $this->properties['options'][] = array(
            'type' => 'end_group'
        );
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @return void
     */
    public function update (SplSubject $subject)
    {
    }

    /**
     * Outputs export header
     *
     * @return bool Whether it succeeded
     */
    public function exportHeader ()
    {
        return true;
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter ()
    {
        return true;
    }

    /**
     * Outputs database header
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBHeader ($db)
    {
        return true;
    }

    /**
     * Outputs database footer
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBFooter ($db)
    {
        return true;
    }

    /**
     * Outputs CREATE DATABASE statement
     *
     * @param string $db Database name
     *
     * @return bool Whether it succeeded
     */
    public function exportDBCreate($db)
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
     * @param string $export_mode 'create_table','triggers','create_view',
     *                            'stand_in'
     * @param string $export_type 'server', 'database', 'table'
     * @param bool   $do_relation whether to include relation comments
     * @param bool   $do_comments whether to include the pmadb-style column
     *                            comments as comments in the structure; this is
     *                            deprecated but the parameter is left here
     *                            because export.php calls PMA_exportStructure()
     *                            also for other export types which use this
     *                            parameter
     * @param bool   $do_mime     whether to include mime comments
     * @param bool   $dates       whether to include creation/update/check dates
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
        $export_mode,
        $export_type,
        $do_relation = false,
        $do_comments = false,
        $do_mime = false,
        $dates = false
    ) {
        switch($export_mode) {
        case 'create_table':
            $columns = PMA_DBI_get_columns($db, $table);
            $columns = array_values($columns);
            $row_cnt = count($columns);

            // Print structure comment
            $output = $this->exportComment(
                "Table structure for "
                . PMA_backquote($table)
            );

            // Begin the table construction
            $output .= "{| class=\"wikitable\" style=\"text-align:center;\""
                     . $this->exportCRLF();

            // Add the table name
            if ($GLOBALS['mediawiki_caption']) {
                $output .= "|+'''" . $table . "'''" . $this->exportCRLF();
            }

            // Add the table headers
            if ($GLOBALS['mediawiki_headers']) {
                $output .= "|- style=\"background:#ffdead;\"" . $this->exportCRLF();
                $output .= "! style=\"background:#ffffff\" | " . $this->exportCRLF();
                for ($i = 0; $i < $row_cnt; ++$i) {
                    $output .= " | " . $columns[$i]['Field']. $this->exportCRLF();
                }
            }

            // Add the table structure
            $output .= "|-" .  $this->exportCRLF();
            $output .= "! Type" . $this->exportCRLF();
            for ($i = 0; $i < $row_cnt; ++$i) {
                $output .= " | " . $columns[$i]['Type'] . $this->exportCRLF();
            }

            $output .= "|-" .  $this->exportCRLF();
            $output .= "! Null" . $this->exportCRLF();
            for ($i = 0; $i < $row_cnt; ++$i) {
                $output .= " | " . $columns[$i]['Null'] . $this->exportCRLF();
            }

            $output .= "|-" .  $this->exportCRLF();
            $output .= "! Default" . $this->exportCRLF();
            for ($i = 0; $i < $row_cnt; ++$i) {
                $output .= " | " . $columns[$i]['Default'] . $this->exportCRLF();
            }

            $output .= "|-" .  $this->exportCRLF();
            $output .= "! Extra" . $this->exportCRLF();
            for ($i = 0; $i < $row_cnt; ++$i) {
                $output .= " | " . $columns[$i]['Extra'] . $this->exportCRLF();
            }

            $output .= "|}" .  str_repeat($this->exportCRLF(), 2);
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
     * @return bool             Whether it succeeded
     *
     * @access public
     */
    function exportData(
        $db,
        $table,
        $crlf,
        $error_url,
        $sql_query
    ) {
        // Print data comment
        $output = $this->exportComment("Table data for ". PMA_backquote($table));

        // Begin the table construction
        // Use the "wikitable" class for style
        // Use the "sortable"  class for allowing tables to be sorted by column
        $output .= "{| class=\"wikitable sortable\" style=\"text-align:center;\""
            . $this->exportCRLF();

        // Add the table name
        if ($GLOBALS['mediawiki_caption']) {
            $output .= "|+'''" . $table . "'''" . $this->exportCRLF();
        }

        // Add the table headers
        if ($GLOBALS['mediawiki_headers']) {
            // Get column names
            $column_names = PMA_DBI_get_column_names($db, $table);

            // Add column names as table headers
            if ( ! is_null($column_names) ) {
                // Use '|-' for separating rows
                $output .= "|-" . $this->exportCRLF();

                // Use '!' for separating table headers
                foreach ($column_names as $column) {
                    $output .= " ! " . $column . "" . $this->exportCRLF();
                }
            }
        }

        // Get the table data from the database
        $result = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
        $fields_cnt = PMA_DBI_num_fields($result);

        while ($row = PMA_DBI_fetch_row($result)) {
            $output .= "|-" . $this->exportCRLF();

            // Use '|' for separating table columns
            for ($i = 0; $i < $fields_cnt; ++ $i) {
                $output .= " | " . $row[$i] . "" . $this->exportCRLF();
            }
        }

        // End table construction
        $output .= "|}" . str_repeat($this->exportCRLF(), 2);
        return PMA_exportOutputHandler($output);
    }

    /**
     * Outputs comments containing info about the exported tables
     *
     * @param string $text Text of comment
     *
     * @return string The formatted comment
     */
    private function exportComment($text = '')
    {
        // see http://www.mediawiki.org/wiki/Help:Formatting
        $comment = $this->exportCRLF();
        $comment .= '<!--' . $this->exportCRLF();
        $comment .= $text  . $this->exportCRLF();
        $comment .= '-->'  . str_repeat($this->exportCRLF(), 2);

        return $comment;
    }

    /**
     * Outputs CRLF
     *
     * @return string CRLF
     */
    private function exportCRLF()
    {
        // The CRLF expected by the mediawiki format is "\n"
        return "\n";
    }
}
?>