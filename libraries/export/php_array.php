<?php
/**
 * Set of functions used to build dumps of tables as PHP Arrays
 *
 * @author  Geoffray Warnants <http://www.geoffray.be>
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
        'text'          => 'strPhpArray',
        'extension'     => 'php',
        'mime_type'     => 'text/plain',
        'options'       => array(
            array(
                'type' => 'hidden',
                'name' => 'data',
            ),
        ),
        'options_text'  => 'strOptions',
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
        . ' * @author Geoffray Warnants' . $GLOBALS['crlf']
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

            $isLastLine = ($i + 1 >= $columns_cnt);

            $column = $columns[$i];

            if (is_null($record[$i])) {
                $buffer .= "'" . $column . "'=>null" . (! $isLastLine ? ',' : '');
            } elseif (is_numeric($record[$i])) {
                $buffer .= "'" . $column . "'=>" . $record[$i] . (! $isLastLine ? ',' : '');
            } else {
                $buffer .= "'" . $column . "'=>'" . addslashes($record[$i]) . "'" . (! $isLastLine ? ',' : '');
            }
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
