<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build YAML dumps of tables
 *
 * @version $Id$
 * @package phpMyAdmin-Export-YAML
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (isset($plugin_list)) {
    $plugin_list['yaml'] = array(
        'text'          => 'YAML',
        'extension'     => 'yml',
        'mime_type'     => 'text/yaml',
        'force_file'    => true,
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
    PMA_exportOutputHandler('# ' . $text . $GLOBALS['crlf']);
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
    PMA_exportOutputHandler('...' . $GLOBALS['crlf']);
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
    PMA_exportOutputHandler('%YAML 1.1' . $GLOBALS['crlf'] . '---' . $GLOBALS['crlf']);
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
            $buffer = '# ' . $db . '.' . $table . $crlf;
            $buffer .= '-' . $crlf;
        } else {
            $buffer = '-' . $crlf;
        }

        for ($i = 0; $i < $columns_cnt; $i++) {
            if (! isset($record[$i])) {
                continue;
            }

            $column = $columns[$i];

            if (is_null($record[$i])) {
                $buffer .= '  ' . $column . ': null' . $crlf;
                continue;
            }

            if (is_numeric($record[$i])) {
                $buffer .= '  ' . $column . ': '  . $record[$i] . $crlf;
                continue;
            }

            $record[$i] = preg_replace('/\r\n|\r|\n/', $crlf.'    ', $record[$i]);
            if (strstr($record[$i], $crlf))
              $record[$i] = '|-' . $crlf . '    '.$record[$i];

            $buffer .= '  ' . $column . ': ' . $record[$i] . $crlf;
        }

        if (! PMA_exportOutputHandler($buffer)) {
            return FALSE;
        }
    }
    PMA_DBI_free_result($result);

    return true;
}

}
?>
