<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of methods used to build dumps of tables as Latex
 *
 * @package    PhpMyAdmin-Export
 * @subpackage Latex
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the export interface */
require_once 'libraries/plugins/ExportPlugin.class.php';

/**
 * Handles the export for the Latex format
 *
 * @package    PhpMyAdmin-Export
 * @subpackage Latex
 */
class ExportLatex extends ExportPlugin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // initialize the specific export sql variables
        $this->initSpecificVariables();

        $this->setProperties();
    }

    /**
     * Initialize the local variables that are used for export Latex
     *
     * @return void
     */
    protected function initSpecificVariables()
    {
        /* Messages used in default captions */
        $GLOBALS['strLatexContent'] = __('Content of table @TABLE@');
        $GLOBALS['strLatexContinued'] = __('(continued)');
        $GLOBALS['strLatexStructure'] = __('Structure of table @TABLE@');
    }

    /**
     * Sets the export Latex properties
     *
     * @return void
     */
    protected function setProperties()
    {
        global $plugin_param;
        $hide_structure = false;
        if ($plugin_param['export_type'] == 'table'
            && ! $plugin_param['single_table']
        ) {
            $hide_structure = true;
        }

        $props = 'libraries/properties/';
        include_once "$props/plugins/ExportPluginProperties.class.php";
        include_once "$props/options/groups/OptionsPropertyRootGroup.class.php";
        include_once "$props/options/groups/OptionsPropertyMainGroup.class.php";
        include_once "$props/options/items/BoolPropertyItem.class.php";
        include_once "$props/options/items/RadioPropertyItem.class.php";
        include_once "$props/options/items/TextPropertyItem.class.php";

        $exportPluginProperties = new ExportPluginProperties();
        $exportPluginProperties->setText('LaTeX');
        $exportPluginProperties->setExtension('tex');
        $exportPluginProperties->setMimeType('application/x-tex');
        $exportPluginProperties->setOptionsText(__('Options'));

        // create the root group that will be the options field for
        // $exportPluginProperties
        // this will be shown as "Format specific options"
        $exportSpecificOptions = new OptionsPropertyRootGroup();
        $exportSpecificOptions->setName("Format Specific Options");

        // general options main group
        $generalOptions = new OptionsPropertyMainGroup();
        $generalOptions->setName("general_opts");
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem();
        $leaf->setName("caption");
        $leaf->setText(__('Include table caption'));
        $generalOptions->addProperty($leaf);
        // add the main group to the root group
        $exportSpecificOptions->addProperty($generalOptions);

        // what to dump (structure/data/both) main group
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

        // structure options main group
        if (! $hide_structure) {
            $structureOptions = new OptionsPropertyMainGroup();
            $structureOptions->setName("structure");
            $structureOptions->setText(__('Object creation options'));
            $structureOptions->setForce('data');
            // create primary items and add them to the group
            $leaf = new TextPropertyItem();
            $leaf->setName("structure_caption");
            $leaf->setText(__('Table caption:'));
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            $leaf = new TextPropertyItem();
            $leaf->setName("structure_continued_caption");
            $leaf->setText(__('Table caption (continued):'));
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            $leaf = new TextPropertyItem();
            $leaf->setName("structure_label");
            $leaf->setText(__('Label key:'));
            $leaf->setDoc('faq6-27');
            $structureOptions->addProperty($leaf);
            if (! empty($GLOBALS['cfgRelation']['relation'])) {
                $leaf = new BoolPropertyItem();
                $leaf->setName("relation");
                $leaf->setText(__('Display foreign key relationships'));
                $structureOptions->addProperty($leaf);
            }
            $leaf = new BoolPropertyItem();
            $leaf->setName("comments");
            $leaf->setText(__('Display comments'));
            $structureOptions->addProperty($leaf);
            if (! empty($GLOBALS['cfgRelation']['mimework'])) {
                $leaf = new BoolPropertyItem();
                $leaf->setName("mime");
                $leaf->setText(__('Display MIME types'));
                $structureOptions->addProperty($leaf);
            }
            // add the main group to the root group
            $exportSpecificOptions->addProperty($structureOptions);
        }

        // data options main group
        $dataOptions = new OptionsPropertyMainGroup();
        $dataOptions->setName("data");
        $dataOptions->setText(__('Data dump options'));
        $dataOptions->setForce('structure');
        // create primary items and add them to the group
        $leaf = new BoolPropertyItem();
        $leaf->setName("columns");
        $leaf->setText(__('Put columns names in the first row:'));
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("data_caption");
        $leaf->setText(__('Table caption:'));
        $leaf->setDoc('faq6-27');
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("data_continued_caption");
        $leaf->setText(__('Table caption (continued):'));
        $leaf->setDoc('faq6-27');
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName("data_label");
        $leaf->setText(__('Label key:'));
        $leaf->setDoc('faq6-27');
        $dataOptions->addProperty($leaf);
        $leaf = new TextPropertyItem();
        $leaf->setName('null');
        $leaf->setText(__('Replace NULL with:'));
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
        global $crlf;
        global $cfg;

        $head = '% phpMyAdmin LaTeX Dump' . $crlf
            . '% version ' . PMA_VERSION . $crlf
            . '% http://www.phpmyadmin.net' . $crlf
            . '%' . $crlf
            . '% ' . __('Host:') . ' ' . $cfg['Server']['host'];
        if (! empty($cfg['Server']['port'])) {
             $head .= ':' . $cfg['Server']['port'];
        }
        $head .= $crlf
            . '% ' . __('Generation Time:') . ' '
            . PMA_Util::localisedDate() . $crlf
            . '% ' . __('Server version:') . ' ' . PMA_MYSQL_STR_VERSION . $crlf
            . '% ' . __('PHP Version:') . ' ' . phpversion() . $crlf;
        return PMA_exportOutputHandler($head);
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
        global $crlf;
        $head = '% ' . $crlf
            . '% ' . __('Database:') . ' ' . '\'' . $db . '\'' . $crlf
            . '% ' . $crlf;
        return PMA_exportOutputHandler($head);
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
     * Outputs the content of a table in JSON format
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
        $result      = $GLOBALS['dbi']->tryQuery(
            $sql_query, null, PMA_DatabaseInterface::QUERY_UNBUFFERED
        );

        $columns_cnt = $GLOBALS['dbi']->numFields($result);
        for ($i = 0; $i < $columns_cnt; $i++) {
            $columns[$i] = $GLOBALS['dbi']->fieldName($result, $i);
        }
        unset($i);

        $buffer = $crlf . '%' . $crlf . '% ' . __('Data:') . ' ' . $table
            . $crlf . '%' . $crlf . ' \\begin{longtable}{|';

        for ($index = 0; $index < $columns_cnt; $index++) {
            $buffer .= 'l|';
        }
        $buffer .= '} ' . $crlf ;

        $buffer .= ' \\hline \\endhead \\hline \\endfoot \\hline ' . $crlf;
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . PMA_Util::expandUserString(
                    $GLOBALS['latex_data_caption'],
                    array(
                        'texEscape',
                        get_class($this),
                        'libraries/plugins/export/' . get_class($this) . ".class.php"
                    ),
                    array('table' => $table, 'database' => $db)
                )
                . '} \\label{'
                . PMA_Util::expandUserString(
                    $GLOBALS['latex_data_label'],
                    null,
                    array('table' => $table, 'database' => $db)
                )
                . '} \\\\';
        }
        if (! PMA_exportOutputHandler($buffer)) {
            return false;
        }

        // show column names
        if (isset($GLOBALS['latex_columns'])) {
            $buffer = '\\hline ';
            for ($i = 0; $i < $columns_cnt; $i++) {
                $buffer .= '\\multicolumn{1}{|c|}{\\textbf{'
                    . self::texEscape(stripslashes($columns[$i])) . '}} & ';
            }

            $buffer = substr($buffer, 0, -2) . '\\\\ \\hline \hline ';
            if (! PMA_exportOutputHandler($buffer . ' \\endfirsthead ' . $crlf)) {
                return false;
            }
            if (isset($GLOBALS['latex_caption'])) {
                if (! PMA_exportOutputHandler(
                    '\\caption{'
                    . PMA_Util::expandUserString(
                        $GLOBALS['latex_data_continued_caption'],
                        array(
                            'texEscape',
                            get_class($this),
                            'libraries/plugins/export/'
                            . get_class($this) . ".class.php"
                        ),
                        array('table' => $table, 'database' => $db)
                    )
                    . '} \\\\ '
                )) {
                    return false;
                }
            }
            if (! PMA_exportOutputHandler($buffer . '\\endhead \\endfoot' . $crlf)) {
                return false;
            }
        } else {
            if (! PMA_exportOutputHandler('\\\\ \hline')) {
                return false;
            }
        }

        // print the whole table
        while ($record = $GLOBALS['dbi']->fetchAssoc($result)) {
            $buffer = '';
            // print each row
            for ($i = 0; $i < $columns_cnt; $i++) {
                if ((! function_exists('is_null')
                    || ! is_null($record[$columns[$i]]))
                    && isset($record[$columns[$i]])
                ) {
                    $column_value = self::texEscape(
                        stripslashes($record[$columns[$i]])
                    );
                } else {
                    $column_value = $GLOBALS['latex_null'];
                }

                // last column ... no need for & character
                if ($i == ($columns_cnt - 1)) {
                    $buffer .= $column_value;
                } else {
                    $buffer .= $column_value . " & ";
                }
            }
            $buffer .= ' \\\\ \\hline ' . $crlf;
            if (! PMA_exportOutputHandler($buffer)) {
                return false;
            }
        }

        $buffer = ' \\end{longtable}' . $crlf;
        if (! PMA_exportOutputHandler($buffer)) {
            return false;
        }

        $GLOBALS['dbi']->freeResult($result);
        return true;
    } // end getTableLaTeX

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
     *                                exportStructure() also for other
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
        global $cfgRelation;

        /* We do not export triggers */
        if ($export_mode == 'triggers') {
            return true;
        }

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
        $buffer      = $crlf . '%' . $crlf . '% ' . __('Structure:') . ' ' . $table
            . $crlf . '%' . $crlf . ' \\begin{longtable}{';
        if (! PMA_exportOutputHandler($buffer)) {
            return false;
        }

        $columns_cnt = 4;
        $alignment = '|l|c|c|c|';
        if ($do_relation && $have_rel) {
            $columns_cnt++;
            $alignment .= 'l|';
        }
        if ($do_comments) {
            $columns_cnt++;
            $alignment .= 'l|';
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $columns_cnt++;
            $alignment .='l|';
        }
        $buffer = $alignment . '} ' . $crlf ;

        $header = ' \\hline ';
        $header .= '\\multicolumn{1}{|c|}{\\textbf{' . __('Column')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Type')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Null')
            . '}} & \\multicolumn{1}{|c|}{\\textbf{' . __('Default') . '}}';
        if ($do_relation && $have_rel) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Links to') . '}}';
        }
        if ($do_comments) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{' . __('Comments') . '}}';
            $comments = PMA_getComments($db, $table);
        }
        if ($do_mime && $cfgRelation['mimework']) {
            $header .= ' & \\multicolumn{1}{|c|}{\\textbf{MIME}}';
            $mime_map = PMA_getMIME($db, $table, true);
        }

        // Table caption for first page and label
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . PMA_Util::expandUserString(
                    $GLOBALS['latex_structure_caption'],
                    array(
                        'texEscape',
                        get_class($this),
                        'libraries/plugins/export/' . get_class($this) . ".class.php"
                    ),
                    array('table' => $table, 'database' => $db)
                )
                . '} \\label{'
                . PMA_Util::expandUserString(
                    $GLOBALS['latex_structure_label'],
                    null,
                    array('table' => $table, 'database' => $db)
                )
                . '} \\\\' . $crlf;
        }
        $buffer .= $header . ' \\\\ \\hline \\hline' . $crlf
            . '\\endfirsthead' . $crlf;
        // Table caption on next pages
        if (isset($GLOBALS['latex_caption'])) {
            $buffer .= ' \\caption{'
                . PMA_Util::expandUserString(
                    $GLOBALS['latex_structure_continued_caption'],
                    array(
                        'texEscape',
                        get_class($this),
                        'libraries/plugins/export/' . get_class($this) . ".class.php"
                    ),
                    array('table' => $table, 'database' => $db)
                )
                . '} \\\\ ' . $crlf;
        }
        $buffer .= $header . ' \\\\ \\hline \\hline \\endhead \\endfoot ' . $crlf;

        if (! PMA_exportOutputHandler($buffer)) {
            return false;
        }

        $fields = $GLOBALS['dbi']->getColumns($db, $table);
        foreach ($fields as $row) {
            $extracted_columnspec
                = PMA_Util::extractColumnSpec(
                    $row['Type']
                );
            $type = $extracted_columnspec['print_type'];
            if (empty($type)) {
                $type = ' ';
            }

            if (! isset($row['Default'])) {
                if ($row['Null'] != 'NO') {
                    $row['Default'] = 'NULL';
                }
            }

            $field_name = $row['Field'];

            $local_buffer = $field_name . "\000" . $type . "\000"
                . (($row['Null'] == '' || $row['Null'] == 'NO')
                    ? __('No') : __('Yes'))
                . "\000" . (isset($row['Default']) ? $row['Default'] : '');

            if ($do_relation && $have_rel) {
                $local_buffer .= "\000";
                if (isset($res_rel[$field_name])) {
                    $local_buffer .= $res_rel[$field_name]['foreign_table'] . ' ('
                        . $res_rel[$field_name]['foreign_field'] . ')';
                }
            }
            if ($do_comments && $cfgRelation['commwork']) {
                $local_buffer .= "\000";
                if (isset($comments[$field_name])) {
                    $local_buffer .= $comments[$field_name];
                }
            }
            if ($do_mime && $cfgRelation['mimework']) {
                $local_buffer .= "\000";
                if (isset($mime_map[$field_name])) {
                    $local_buffer .= str_replace(
                        '_',
                        '/',
                        $mime_map[$field_name]['mimetype']
                    );
                }
            }
            $local_buffer = self::texEscape($local_buffer);
            if ($row['Key']=='PRI') {
                $pos=strpos($local_buffer, "\000");
                $local_buffer = '\\textit{'
                    . substr($local_buffer, 0, $pos)
                    . '}' . substr($local_buffer, $pos);
            }
            if (in_array($field_name, $unique_keys)) {
                $pos=strpos($local_buffer, "\000");
                $local_buffer = '\\textbf{'
                    . substr($local_buffer, 0, $pos)
                    . '}' . substr($local_buffer, $pos);
            }
            $buffer = str_replace("\000", ' & ', $local_buffer);
            $buffer .= ' \\\\ \\hline ' . $crlf;

            if (! PMA_exportOutputHandler($buffer)) {
                return false;
            }
        } // end while

        $buffer = ' \\end{longtable}' . $crlf;
        return PMA_exportOutputHandler($buffer);
    } // end of the 'exportStructure' method

    /**
     * Escapes some special characters for use in TeX/LaTeX
     *
     * @param string $string the string to convert
     *
     * @return string the converted string with escape codes
     */
    public static function texEscape($string)
    {
        $escape = array('$', '%', '{', '}',  '&',  '#', '_', '^');
        $cnt_escape = count($escape);
        for ($k = 0; $k < $cnt_escape; $k++) {
            $string = str_replace($escape[$k], '\\' . $escape[$k], $string);
        }
        return $string;
    }
}
?>
