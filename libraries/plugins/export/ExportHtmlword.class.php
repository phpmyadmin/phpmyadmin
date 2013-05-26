<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * HTML-Word export code
 *
 * @package    PhpMyAdmin-Export
 * @subpackage HTML-Word
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the export interface */
require_once 'libraries/plugins/ExportPlugin.class.php';

/**
 * Handles the export for the HTML-Word format
 *
 * @package    PhpMyAdmin-Export
 * @subpackage HTML-Word
 */
class ExportHtmlword extends ExportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setProperties();
    }

    /**
     * Sets the export HTML-Word properties
     *
     * @return void
     */
    protected function setProperties()
    {
        $props = 'libraries/properties/';
        include_once "$props/plugins/ExportPluginProperties.class.php";
        include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
        include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
        include_once "$props/options/items/RadioPropertyItem.class.php";
        include_once "$props/options/items/TextPropertyItem.class.php";
        include_once "$props/options/items/BoolPropertyItem.class.php";

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('Microsoft Word 2000');
        $exportPluginProperties->setExtension('doc');
        $exportPluginProperties->setMimeType('application/vnd.ms-word');
        $exportPluginProperties->setForceFile(true);
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup();
        $exportSpecificOptions->setName("Format Specific Options");

        // what to dump (structure/data/both)
        $dumpWhat = new OptionsPropertyMainGroup();
        $dumpWhat->setName("dump_what");
        $dumpWhat->setText(__('Dump table'));
        // create primary items and add them to the group
        $leaf = new RadioPropertyItem();
        $leaf->setName("structure_or_data");
        $leaf->setValues(
            array(
                'structure' => __('structure'),
                'data' => __('data'),
                'structure_and_data' => __('structure and data')
            )
        );
        $dumpWhat->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($dumpWhat);

        // data options main group
        $dataOptions = new OptionsPropertyMainGroup();
        $dataOptions->setName("dump_what");
        $dataOptions->setText(__('Data dump options'));
        $dataOptions->setForce('structure');
        // create primary items and add them to the group
        $leaf = new TextPropertyItem();
        $leaf->setName("null");
        $leaf->setText(__('Replace NULL with:'));
        $dataOptions->addProperty($leaf);
        $leaf = new BoolPropertyItem();
        $leaf->setName("columns");
        $leaf->setText(__('Put columns names in the first row'));
        $dataOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($dataOptions);

        // set the options for the export plugin property item
        $exportPluginProperties->setOptions($exportSpecificOptions);
        $this->properties = $exportPluginProperties;
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
        global $charset_of_file;

        return PMA_exportOutputHandler(
            '<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:word"
            xmlns="http://www.w3.org/TR/REC-html40">

            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html>
            <head>
                <meta http-equiv="Content-type" content="text/html;charset='
            . (isset($charset_of_file) ? $charset_of_file : 'utf-8') . '" />
            </head>
            <body>'
        );
    }

    /**
     * Outputs export footer
     *
     * @return bool Whether it succeeded
     */
    public function exportFooter ()
    {
        return PMA_exportOutputHandler('</body></html>');
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
        return PMA_exportOutputHandler(
            '<h1>' . __('Database') . ' ' . htmlspecialchars($db) . '</h1>'
        );
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
     * Outputs the content of a table in HTML-Word format
     *
     * @param string $db        database name
     * @param string $table     table name
     * @param string $crlf      the end of line sequence
     * @param string $error_url the url to go back in case of error
     * @param string $sql_query SQL query for obtaining data
     *
     * @return bool Whether it succeeded
     */
    public function exportData($db, $table, $crlf, $error_url, $sql_query)
    {
        global $what;

        if (! PMA_exportOutputHandler(
            '<h2>'
            . __('Dumping data for table') . ' ' . htmlspecialchars($table)
            . '</h2>'
        )) {
            return false;
        }
        if (! PMA_exportOutputHandler(
            '<table class="width100" cellspacing="1">'
        )) {
            return false;
        }

        // Gets the data from the database
        $result = $GLOBALS['dbi']->query(
            $sql_query, null, PMA_DatabaseInterface::QUERY_UNBUFFERED
        );
        $fields_cnt = $GLOBALS['dbi']->numFields($result);

        // If required, get fields name at the first line
        if (isset($GLOBALS['htmlword_columns'])) {
            $schema_insert = '<tr class="print-category">';
            for ($i = 0; $i < $fields_cnt; $i++) {
                $schema_insert .= '<td class="print"><strong>'
                    . htmlspecialchars(
                        stripslashes($GLOBALS['dbi']->fieldName($result, $i))
                    )
                    . '</strong></td>';
            } // end for
            $schema_insert .= '</tr>';
            if (! PMA_exportOutputHandler($schema_insert)) {
                return false;
            }
        } // end if

        // Format the data
        while ($row = $GLOBALS['dbi']->fetchRow($result)) {
            $schema_insert = '<tr class="print-category">';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (! isset($row[$j]) || is_null($row[$j])) {
                    $value = $GLOBALS[$what . '_null'];
                } elseif ($row[$j] == '0' || $row[$j] != '') {
                    $value = $row[$j];
                } else {
                    $value = '';
                }
                $schema_insert .= '<td class="print">'
                    . htmlspecialchars($value)
                    . '</td>';
            } // end for
            $schema_insert .= '</tr>';
            if (! PMA_exportOutputHandler($schema_insert)) {
                return false;
            }
        } // end while
        $GLOBALS['dbi']->freeResult($result);
        if (! PMA_exportOutputHandler('</table>')) {
            return false;
        }

        return true;
    }

    /**
     * Returns a stand-in CREATE definition to resolve view dependencies
     *
     * @param string $db   the database name
     * @param string $view the view name
     * @param string $crlf the end of line sequence
     *
     * @return string resulting definition
     */
    public function getTableDefStandIn($db, $view, $crlf)
    {
        $schema_insert = '<table class="width100" cellspacing="1">'
            . '<tr class="print-category">'
            . '<th class="print">'
            . __('Column')
            . '</th>'
            . '<td class="print"><strong>'
            . __('Type')
            . '</strong></td>'
            . '<td class="print"><strong>'
            . __('Null')
            . '</strong></td>'
            . '<td class="print"><strong>'
            . __('Default')
            . '</strong></td>'
            . '</tr>';

        /**
         * Get the unique keys in the view
         */
        $unique_keys = array();
        $keys = $GLOBALS['dbi']->getTableIndexes($db, $view);
        foreach ($keys as $key) {
            if ($key['Non_unique'] == 0) {
                $unique_keys[] = $key['Column_name'];
            }
        }

        $columns = $GLOBALS['dbi']->getColumns($db, $view);
        foreach ($columns as $column) {
            $schema_insert .= $this->formatOneColumnDefinition(
                $column,
                $unique_keys
            );
            $schema_insert .= '</tr>';
        }

        $schema_insert .= '</table>';
        return $schema_insert;
    }

    /**
     * Returns $table's CREATE definition
     *
     * @param string $db            the database name
     * @param string $table         the table name
     * @param string $crlf          the end of line sequence
     * @param string $error_url     the url to go back in case of error
     * @param bool   $do_relation   whether to include relation comments
     * @param bool   $do_comments   whether to include the pmadb-style column
     *                                comments as comments in the structure;
     *                                this is deprecated but the parameter is
     *                                left here because export.php calls
     *                                PMA_exportStructure() also for other
     *                                export types which use this parameter
     * @param bool   $do_mime       whether to include mime comments
     * @param bool   $show_dates    whether to include creation/update/check dates
     * @param bool   $add_semicolon whether to add semicolon and end-of-line
     *                                at the end
     * @param bool   $view          whether we're handling a view
     *
     * @return string resulting schema
     */
    public function getTableDef(
        $db,
        $table,
        $crlf,
        $error_url,
        $do_relation,
        $do_comments,
        $do_mime,
        $show_dates = false,
        $add_semicolon = true,
        $view = false
    ) {
        // set $cfgRelation here, because there is a chance that it's modified
        // since the class initialization
        global $cfgRelation;

        $schema_insert = '';

        /**
         * Gets fields properties
         */
        $GLOBALS['dbi']->selectDb($db);

        // Check if we can use Relations
        if ($do_relation && ! empty($cfgRelation['relation'])) {
            // Find which tables are related with the current one and write it in
            // an array
            $res_rel = PMA_getForeigners($db, $table);

            if ($res_rel && count($res_rel) > 0) {
                $have_rel = true;
            } else {
                $have_rel = false;
            }
        } else {
               $have_rel = false;
        } // end if

        /**
         * Displays the table structure
         */
        $schema_insert .= '<table class="width100" cellspacing="1">';

        $columns_cnt = 4;
        if ($do_relation && $have_rel) {
            $columns_cnt++;
        }
        if ($do_comments && $cfgRelation['commwork']) {
            $columns_cnt++;
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $columns_cnt++;
        }

        $schema_insert .= '<tr class="print-category">';
        $schema_insert .= '<th class="print">'
            . __('Column')
            . '</th>';
        $schema_insert .= '<td class="print"><strong>'
            . __('Type')
            . '</strong></td>';
        $schema_insert .= '<td class="print"><strong>'
            . __('Null')
            . '</strong></td>';
        $schema_insert .= '<td class="print"><strong>'
            . __('Default')
            . '</strong></td>';
        if ($do_relation && $have_rel) {
            $schema_insert .= '<td class="print"><strong>'
                . __('Links to')
                . '</strong></td>';
        }
        if ($do_comments) {
            $schema_insert .= '<td class="print"><strong>'
                . __('Comments')
                . '</strong></td>';
            $comments = PMA_getComments($db, $table);
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $schema_insert .= '<td class="print"><strong>'
                . htmlspecialchars('MIME')
                . '</strong></td>';
            $mime_map = PMA_getMIME($db, $table, true);
        }
        $schema_insert .= '</tr>';

        $columns = $GLOBALS['dbi']->getColumns($db, $table);
        /**
         * Get the unique keys in the table
         */
        $unique_keys = array();
        $keys = $GLOBALS['dbi']->getTableIndexes($db, $table);
        foreach ($keys as $key) {
            if ($key['Non_unique'] == 0) {
                $unique_keys[] = $key['Column_name'];
            }
        }
        foreach ($columns as $column) {
            $schema_insert .= $this->formatOneColumnDefinition(
                $column,
                $unique_keys
            );
            $field_name = $column['Field'];

            if ($do_relation && $have_rel) {
                $schema_insert .= '<td class="print">'
                    . (isset($res_rel[$field_name])
                        ? htmlspecialchars(
                            $res_rel[$field_name]['foreign_table']
                            . ' (' . $res_rel[$field_name]['foreign_field']
                            . ')'
                        )
                        : '') . '</td>';
            }
            if ($do_comments && $cfgRelation['commwork']) {
                $schema_insert .= '<td class="print">'
                    . (isset($comments[$field_name])
                        ? htmlspecialchars($comments[$field_name])
                        : '') . '</td>';
            }
            if ($do_mime && $cfgRelation['mimework']) {
                $schema_insert .= '<td class="print">'
                    . (isset($mime_map[$field_name]) ?
                    htmlspecialchars(
                        str_replace('_', '/', $mime_map[$field_name]['mimetype'])
                    )
                    : '') . '</td>';
            }

            $schema_insert .= '</tr>';
        } // end foreach

        $schema_insert .= '</table>';
        return $schema_insert;
    }

    /**
     * Outputs triggers
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string Formatted triggers list
     */
    protected function getTriggers($db, $table)
    {
        $dump = '<table class="width100" cellspacing="1">';
        $dump .= '<tr class="print-category">';
        $dump .= '<th class="print">' . __('Name') . '</th>';
        $dump .= '<td class="print"><strong>' . __('Time') . '</strong></td>';
        $dump .= '<td class="print"><strong>' . __('Event') . '</strong></td>';
        $dump .= '<td class="print"><strong>' . __('Definition') . '</strong></td>';
        $dump .= '</tr>';

        $triggers = $GLOBALS['dbi']->getTriggers($db, $table);

        foreach ($triggers as $trigger) {
            $dump .= '<tr class="print-category">';
            $dump .= '<td class="print">'
                . htmlspecialchars($trigger['name'])
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger['action_timing'])
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger['event_manipulation'])
                . '</td>'
                . '<td class="print">'
                . htmlspecialchars($trigger['definition'])
                . '</td>'
                . '</tr>';
        }

        $dump .= '</table>';
        return $dump;
    }

    /**
     * Outputs table's structure
     *
     * @param string $db          database name
     * @param string $table       table name
     * @param string $crlf        the end of line sequence
     * @param string $error_url   the url to go back in case of error
     * @param string $export_mode 'create_table', 'triggers', 'create_view',
     *                            'stand_in'
     * @param string $export_type 'server', 'database', 'table'
     * @param bool   $do_relation whether to include relation comments
     * @param bool   $do_comments whether to include the pmadb-style column
     *                                comments as comments in the structure;
     *                                this is deprecated but the parameter is
     *                                left here because export.php calls
     *                                PMA_exportStructure() also for other
     *                                export types which use this parameter
     * @param bool   $do_mime     whether to include mime comments
     * @param bool   $dates       whether to include creation/update/check dates
     *
     * @return bool Whether it succeeded
     */
    public function exportStructure(
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
        $dump = '';

        switch($export_mode) {
        case 'create_table':
            $dump .= '<h2>'
                . __('Table structure for table') . ' ' . htmlspecialchars($table)
                . '</h2>';
            $dump .= $this->getTableDef(
                $db, $table, $crlf, $error_url, $do_relation, $do_comments, $do_mime,
                $dates
            );
            break;
        case 'triggers':
            $dump = '';
            $triggers = $GLOBALS['dbi']->getTriggers($db, $table);
            if ($triggers) {
                $dump .= '<h2>'
                    . __('Triggers') . ' ' . htmlspecialchars($table)
                    . '</h2>';
                $dump .= $this->getTriggers($db, $table);
            }
            break;
        case 'create_view':
            $dump .= '<h2>'
                . __('Structure for view') . ' ' . htmlspecialchars($table)
                . '</h2>';
            $dump .= $this->getTableDef(
                $db, $table, $crlf, $error_url, $do_relation, $do_comments, $do_mime,
                $dates, true, true
            );
            break;
        case 'stand_in':
            $dump .=  '<h2>'
                . __('Stand-in structure for view') . ' ' . htmlspecialchars($table)
                . '</h2>';
            // export a stand-in definition to resolve view dependencies
            $dump .= $this->getTableDefStandIn($db, $table, $crlf);
        } // end switch

        return PMA_exportOutputHandler($dump);
    }

    /**
     * Formats the definition for one column
     *
     * @param array $column      info about this column
     * @param array $unique_keys unique keys of the table
     *
     * @return string Formatted column definition
     */
    protected function formatOneColumnDefinition(
        $column, $unique_keys
    ) {
        $definition = '<tr class="print-category">';

        $extracted_columnspec
            = PMA_Util::extractColumnSpec($column['Type']);

        $type = htmlspecialchars($extracted_columnspec['print_type']);
        if (empty($type)) {
            $type = '&nbsp;';
        }

        if (! isset($column['Default'])) {
            if ($column['Null'] != 'NO') {
                $column['Default'] = 'NULL';
            }
        }

        $fmt_pre = '';
        $fmt_post = '';
        if (in_array($column['Field'], $unique_keys)) {
            $fmt_pre = '<strong>' . $fmt_pre;
            $fmt_post = $fmt_post . '</strong>';
        }
        if ($column['Key'] == 'PRI') {
            $fmt_pre = '<em>' . $fmt_pre;
            $fmt_post = $fmt_post . '</em>';
        }
        $definition .= '<td class="print">' . $fmt_pre
            . htmlspecialchars($column['Field']) . $fmt_post . '</td>';
        $definition .= '<td class="print">' . htmlspecialchars($type)
            . '</td>';
        $definition .= '<td class="print">'
            . (($column['Null'] == '' || $column['Null'] == 'NO')
                ? __('No')
                : __('Yes'))
            . '</td>';
        $definition .= '<td class="print">'
            . htmlspecialchars(
                isset($column['Default'])
                ? $column['Default']
                : ''
            )
            . '</td>';

        return $definition;
    }
}
?>
