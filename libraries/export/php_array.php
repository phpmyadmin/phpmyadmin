<?php
/**
 * Set of functions used to build dumps of tables as PHP Arrays
 *
 * @version 0.2b (20090704)
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
 * Outputs comment
 *
 * @param   string      Text of comment
 *
 * @return  bool        Whether it suceeded
 */
function PMA_exportComment($text)
{
    PMA_exportOutputHandler('// ' . $text . $GLOBALS['crlf']);
    return true;
}

/**
 * Outputs export footer
 *
 * @return  bool        Whether it suceeded
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
 * @return  bool        Whether it suceeded
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
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBHeader($db)
{
    PMA_exportOutputHandler('//' . $GLOBALS['crlf'] . '// Database "' . $db . '"' . $GLOBALS['crlf'] . '//' . $GLOBALS['crlf']);
    return true;
}

/**
 * Outputs database footer
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBFooter($db)
{
    return true;
}

/**
 * Outputs create database database
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBCreate($db)
{
    return true;
}

/**
 * Outputs the content of a table in YAML format
 *
 * @param   string      the database name
 * @param   string      the table name
 * @param   string      the end of line sequence
 * @param   string      the url to go back in case of error
 * @param   string      SQL query for obtaining data
 *
 * @return  bool        Whether it suceeded
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

    $buffer = '';
    $record_cnt = 0;
    while ($record = PMA_DBI_fetch_row($result)) {

        $record_cnt++;

        // Output table name as comment if this is the first record of the table
        if ($record_cnt == 1) {
            $buffer .= $crlf . '// ' . $db . '.' . $table . $crlf;
            $buffer .= '$' . $table . ' = array(' . $crlf;
            $buffer .= '  array(';
        } else {
            $buffer .= ',' . $crlf . '  array(';
        }


        for ($i = 0; $i < $columns_cnt; $i++) {
            $buffer .= "'" . $columns[$i]. "'=>" . var_export($record[$i], true) . (($i + 1 >= $columns_cnt) ? '' : ',');
        }

        $buffer .= ')';
    }

    $buffer .= $crlf . ');' . $crlf;
    if (! PMA_exportOutputHandler($buffer)) {
        return FALSE;
    }

    PMA_DBI_free_result($result);

    return true;
}

}
