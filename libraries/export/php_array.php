<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build dumps of tables as PHP Arrays
 *
 * @package PhpMyAdmin-Export
 * @subpackage PHP
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (isset($plugin_list)) {
    $plugin_list['php_array'] = array(
        'text'          => __('PHP array'),
        'extension'     => 'php',
        'mime_type'     => 'text/plain',
        'options'       => array(
        array('type' => 'begin_group', 'name' => 'general_opts'),
            array(
                'type' => 'hidden',
                'name' => 'structure_or_data',
            ),
        array('type' => 'end_group')
        ),
        'options_text'  => __('Options'),
    );
} else {

    /**
     * Set of functions used to build exports of tables
     */

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
        PMA_exportOutputHandler(
              '<?php' . $GLOBALS['crlf']
            . '/**' . $GLOBALS['crlf']
            . ' * Export to PHP Array plugin for PHPMyAdmin' . $GLOBALS['crlf']
            . ' * @version 0.2b' . $GLOBALS['crlf']
            . ' */' . $GLOBALS['crlf'] . $GLOBALS['crlf']
        );
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
        PMA_exportOutputHandler('//' . $GLOBALS['crlf'] . '// Database ' . PMA_backquote($db) . $GLOBALS['crlf'] . '//' . $GLOBALS['crlf']);
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
     * Outputs the content of a table as a fragment of PHP code
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
        $result      = PMA_DBI_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);

        $columns_cnt = PMA_DBI_num_fields($result);
        for ($i = 0; $i < $columns_cnt; $i++) {
            $columns[$i] = stripslashes(PMA_DBI_field_name($result, $i));
        }
        unset($i);

        // fix variable names (based on http://www.php.net/manual/language.variables.basics.php)
        if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $table) == false) {
            // fix invalid chars in variable names by replacing them with underscores
            $tablefixed = preg_replace('/[^a-zA-Z0-9_\x7f-\xff]/', '_', $table);

            // variable name must not start with a number or dash...
            if (preg_match('/^[a-zA-Z_\x7f-\xff]/', $tablefixed) == false) {
                $tablefixed = '_' . $tablefixed;
            }
        } else {
            $tablefixed = $table;
        }

        $buffer = '';
        $record_cnt = 0;
        while ($record = PMA_DBI_fetch_row($result)) {

            $record_cnt++;

            // Output table name as comment if this is the first record of the table
            if ($record_cnt == 1) {
                $buffer .= $crlf . '// ' . PMA_backquote($db) . '.' . PMA_backquote($table) . $crlf;
                $buffer .= '$' . $tablefixed . ' = array(' . $crlf;
                $buffer .= '  array(';
            } else {
                $buffer .= ',' . $crlf . '  array(';
            }

            for ($i = 0; $i < $columns_cnt; $i++) {
                $buffer .= var_export($columns[$i], true) . " => " . var_export($record[$i], true) . (($i + 1 >= $columns_cnt) ? '' : ',');
            }

            $buffer .= ')';
        }

        $buffer .= $crlf . ');' . $crlf;
        if (! PMA_exportOutputHandler($buffer)) {
            return false;
        }

        PMA_DBI_free_result($result);

        return true;
    }

}
