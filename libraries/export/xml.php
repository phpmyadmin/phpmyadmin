<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build XML dumps of tables
 *
 * @package PhpMyAdmin-Export
 * @subpackage XML
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

if (!strlen($GLOBALS['db'])) { /* Can't do server export */
    return;
}

if (isset($plugin_list)) {
    $plugin_list['xml'] = array(
        'text' => __('XML'),
        'extension' => 'xml',
        'mime_type' => 'text/xml',
        'options' => array(
            array('type' => 'begin_group', 'name' => 'general_opts'),
            array('type' => 'hidden', 'name' => 'structure_or_data'),
            array('type' => 'end_group')
            ),
        'options_text' => __('Options')
        );

    /* Export structure */
    $plugin_list['xml']['options'][] = array(
        'type' => 'begin_group',
        'name' => 'structure',
        'text' => __('Object creation options (all are recommended)')
        );
    if (!PMA_DRIZZLE) {
        $plugin_list['xml']['options'][] = array(
            'type' => 'bool',
            'name' => 'export_functions',
            'text' => __('Functions')
            );
        $plugin_list['xml']['options'][] = array(
            'type' => 'bool',
            'name' => 'export_procedures',
            'text' => __('Procedures')
            );
    }
    $plugin_list['xml']['options'][] = array(
        'type' => 'bool',
        'name' => 'export_tables',
        'text' => __('Tables')
        );
    if (!PMA_DRIZZLE) {
        $plugin_list['xml']['options'][] = array(
            'type' => 'bool',
            'name' => 'export_triggers',
            'text' => __('Triggers')
            );
        $plugin_list['xml']['options'][] = array(
            'type' => 'bool',
            'name' => 'export_views',
            'text' => __('Views')
            );
    }
    $plugin_list['xml']['options'][] = array(
        'type' => 'end_group'
        );

    /* Data */
    $plugin_list['xml']['options'][] = array(
        'type' => 'begin_group',
        'name' => 'data',
        'text' => __('Data dump options')
        );
    $plugin_list['xml']['options'][] = array(
        'type' => 'bool',
        'name' => 'export_contents',
        'text' => __('Export contents')
        );
    $plugin_list['xml']['options'][] = array(
        'type' => 'end_group'
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
        $foot = '</pma_xml_export>';

        return PMA_exportOutputHandler($foot);
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
        global $crlf;
        global $cfg;
        global $db;
        global $table;
        global $tables;

        $export_struct = isset($GLOBALS['xml_export_functions']) || isset($GLOBALS['xml_export_procedures'])
            || isset($GLOBALS['xml_export_tables']) || isset($GLOBALS['xml_export_triggers'])
            || isset($GLOBALS['xml_export_views']);
        $export_data = isset($GLOBALS['xml_export_contents']) ? true : false;

        if ($GLOBALS['output_charset_conversion']) {
            $charset = $GLOBALS['charset_of_file'];
        } else {
            $charset = 'utf-8';
        }

        $head  =  '<?xml version="1.0" encoding="' . $charset . '"?>' . $crlf
               .  '<!--' . $crlf
               .  '- phpMyAdmin XML Dump' . $crlf
               .  '- version ' . PMA_VERSION . $crlf
               .  '- http://www.phpmyadmin.net' . $crlf
               .  '-' . $crlf
               .  '- ' . __('Host') . ': ' . $cfg['Server']['host'];
        if (!empty($cfg['Server']['port'])) {
             $head .= ':' . $cfg['Server']['port'];
        }
        $head .= $crlf
               .  '- ' . __('Generation Time') . ': ' . PMA_localisedDate() . $crlf
               .  '- ' . __('Server version') . ': ' . PMA_MYSQL_STR_VERSION . $crlf
               .  '- ' . __('PHP Version') . ': ' . phpversion() . $crlf
               .  '-->' . $crlf . $crlf;

        $head .= '<pma_xml_export version="1.0"' . (($export_struct) ? ' xmlns:pma="http://www.phpmyadmin.net/some_doc_url/"' : '') . '>' . $crlf;

        if ($export_struct) {
            if (PMA_DRIZZLE) {
                $result = PMA_DBI_fetch_result("
                    SELECT
                        'utf8' AS DEFAULT_CHARACTER_SET_NAME,
                        DEFAULT_COLLATION_NAME
                    FROM data_dictionary.SCHEMAS
                    WHERE SCHEMA_NAME = '" . PMA_sqlAddSlashes($db) . "'");
            } else {
                $result = PMA_DBI_fetch_result('SELECT `DEFAULT_CHARACTER_SET_NAME`, `DEFAULT_COLLATION_NAME` FROM `information_schema`.`SCHEMATA` WHERE `SCHEMA_NAME` = \''.PMA_sqlAddSlashes($db).'\' LIMIT 1');
            }
            $db_collation = $result[0]['DEFAULT_COLLATION_NAME'];
            $db_charset = $result[0]['DEFAULT_CHARACTER_SET_NAME'];

            $head .= '    <!--' . $crlf;
            $head .= '    - Structure schemas' . $crlf;
            $head .= '    -->' . $crlf;
            $head .= '    <pma:structure_schemas>' . $crlf;
            $head .= '        <pma:database name="' . htmlspecialchars($db) . '" collation="' . $db_collation . '" charset="' . $db_charset . '">' . $crlf;

            if (count($tables) == 0) {
                $tables[] = $table;
            }

            foreach ($tables as $table) {
                // Export tables and views
                $result = PMA_DBI_fetch_result('SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table), 0);
                $tbl =  $result[$table][1];

                $is_view = PMA_Table::isView($db, $table);

                if ($is_view) {
                    $type = 'view';
                } else {
                    $type = 'table';
                }

                if ($is_view && ! isset($GLOBALS['xml_export_views'])) {
                    continue;
                }

                if (! $is_view && ! isset($GLOBALS['xml_export_tables'])) {
                    continue;
                }

                $head .= '            <pma:' . $type . ' name="' . $table . '">' . $crlf;

                $tbl = "                " . htmlspecialchars($tbl);
                $tbl = str_replace("\n", "\n                ", $tbl);

                $head .= $tbl . ';' . $crlf;
                $head .= '            </pma:' . $type . '>' . $crlf;

                if (isset($GLOBALS['xml_export_triggers']) && $GLOBALS['xml_export_triggers']) {
                    // Export triggers
                    $triggers = PMA_DBI_get_triggers($db, $table);
                    if ($triggers) {
                        foreach ($triggers as $trigger) {
                            $code = $trigger['create'];
                            $head .= '            <pma:trigger name="' . $trigger['name'] . '">' . $crlf;

                            // Do some formatting
                            $code = substr(rtrim($code), 0, -3);
                            $code = "                " . htmlspecialchars($code);
                            $code = str_replace("\n", "\n                ", $code);

                            $head .= $code . $crlf;
                            $head .= '            </pma:trigger>' . $crlf;
                        }

                        unset($trigger);
                        unset($triggers);
                    }
                }
            }

            if (isset($GLOBALS['xml_export_functions']) && $GLOBALS['xml_export_functions']) {
                // Export functions
                $functions = PMA_DBI_get_procedures_or_functions($db, 'FUNCTION');
                if ($functions) {
                    foreach ($functions as $function) {
                        $head .= '            <pma:function name="' . $function . '">' . $crlf;

                        // Do some formatting
                        $sql = PMA_DBI_get_definition($db, 'FUNCTION', $function);
                        $sql = rtrim($sql);
                        $sql = "                " . htmlspecialchars($sql);
                        $sql = str_replace("\n", "\n                ", $sql);

                        $head .= $sql . $crlf;
                        $head .= '            </pma:function>' . $crlf;
                    }

                    unset($create_func);
                    unset($function);
                    unset($functions);
                }
            }

            if (isset($GLOBALS['xml_export_procedures']) && $GLOBALS['xml_export_procedures']) {
                // Export procedures
                $procedures = PMA_DBI_get_procedures_or_functions($db, 'PROCEDURE');
                if ($procedures) {
                    foreach ($procedures as $procedure) {
                        $head .= '            <pma:procedure name="' . $procedure . '">' . $crlf;

                        // Do some formatting
                        $sql = PMA_DBI_get_definition($db, 'PROCEDURE', $procedure);
                        $sql = rtrim($sql);
                        $sql = "                " . htmlspecialchars($sql);
                        $sql = str_replace("\n", "\n                ", $sql);

                        $head .= $sql . $crlf;
                        $head .= '            </pma:procedure>' . $crlf;
                    }

                    unset($create_proc);
                    unset($procedure);
                    unset($procedures);
                }
            }

            unset($result);

            $head .= '        </pma:database>' . $crlf;
            $head .= '    </pma:structure_schemas>' . $crlf;

            if ($export_data) {
                $head .= $crlf;
            }
        }

        return PMA_exportOutputHandler($head);
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
        global $crlf;

        if (isset($GLOBALS['xml_export_contents']) && $GLOBALS['xml_export_contents']) {
            $head = '    <!--' . $crlf
                  . '    - ' . __('Database') . ': ' .  '\'' . $db . '\'' . $crlf
                  . '    -->' . $crlf
                  . '    <database name="' . htmlspecialchars($db) . '">' . $crlf;

            return PMA_exportOutputHandler($head);
        } else {
            return true;
        }
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
        global $crlf;

        if (isset($GLOBALS['xml_export_contents']) && $GLOBALS['xml_export_contents']) {
            return PMA_exportOutputHandler('    </database>' . $crlf);
        } else {
            return true;
        }
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
     * Outputs the content of a table in XML format
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
    function PMA_exportData($db, $table, $crlf, $error_url, $sql_query)
    {

        if (isset($GLOBALS['xml_export_contents']) && $GLOBALS['xml_export_contents']) {
            $result      = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);

            $columns_cnt = PMA_DBI_num_fields($result);
            $columns = array();
            for ($i = 0; $i < $columns_cnt; $i++) {
                $columns[$i] = stripslashes(str_replace(' ', '_', PMA_DBI_field_name($result, $i)));
            }
            unset($i);

            $buffer      = '        <!-- ' . __('Table') . ' ' . $table . ' -->' . $crlf;
            if (!PMA_exportOutputHandler($buffer)) {
                return false;
            }

            while ($record = PMA_DBI_fetch_row($result)) {
                $buffer         = '        <table name="' . htmlspecialchars($table) . '">' . $crlf;
                for ($i = 0; $i < $columns_cnt; $i++) {
                    // If a cell is NULL, still export it to preserve the XML structure
                    if (!isset($record[$i]) || is_null($record[$i])) {
                        $record[$i] = 'NULL';
                    }
                    $buffer .= '            <column name="' . htmlspecialchars($columns[$i]) . '">' . htmlspecialchars((string)$record[$i])
                            .  '</column>' . $crlf;
                }
                $buffer         .= '        </table>' . $crlf;

                if (!PMA_exportOutputHandler($buffer)) {
                    return false;
                }
            }
            PMA_DBI_free_result($result);
        }

        return true;
    } // end of the 'PMA_getTableXML()' function
}
?>
